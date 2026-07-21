<?php
require("../template/top.php");

// Admin only.
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$notice = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $notice = array('error', 'Session expired — please retry.');
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'create') {
            $subject = trim(@$_POST['subject']);
            $body = (string) @$_POST['body'];
            if (strlen($subject) < 3 || strlen(trim($body)) < 10) {
                $notice = array('error', 'Give the campaign a subject and some body content.');
            } else {
                $db->query('INSERT INTO newsletter_campaigns (subject, body, status, created_by) VALUES ("'
                    . $db->real_escape_string($subject) . '", "'
                    . $db->real_escape_string($body) . '", "draft", "'
                    . $db->real_escape_string($userinfo['id']) . '")');
                $notice = array('ok', 'Draft created. Review it, then Start sending.');
            }
        } elseif ($action === 'start') {
            $cid = (int) @$_POST['campaign_id'];
            $c = $db->query('SELECT status FROM newsletter_campaigns WHERE id = "' . $db->real_escape_string($cid) . '"');
            if ($c && $c->num_rows && $c->fetch_assoc()['status'] === 'draft') {
                // Enqueue every subscribed address once.
                $db->query('INSERT INTO newsletter_queue (campaign_id, email)
                    SELECT "' . $db->real_escape_string($cid) . '", email FROM newsletter_signups WHERE unsubscribed = 0');
                $total = $db->affected_rows;
                $db->query('UPDATE newsletter_campaigns SET status = "sending", total_recipients = "'
                    . $db->real_escape_string($total) . '" WHERE id = "' . $db->real_escape_string($cid) . '"');
                $notice = array('ok', "Started — {$total} recipients queued. Sending drips out within the daily limit.");
            } else {
                $notice = array('error', 'Campaign is not a draft.');
            }
        } elseif ($action === 'pause' || $action === 'resume') {
            $cid = (int) @$_POST['campaign_id'];
            $new = $action === 'pause' ? 'paused' : 'sending';
            $from = $action === 'pause' ? 'sending' : 'paused';
            $db->query('UPDATE newsletter_campaigns SET status = "' . $new . '" WHERE id = "'
                . $db->real_escape_string($cid) . '" AND status = "' . $from . '"');
            $notice = array('ok', 'Campaign ' . ($action === 'pause' ? 'paused' : 'resumed') . '.');
        }
    }
}

$subs = (int) $db->query('SELECT COUNT(*) c FROM newsletter_signups WHERE unsubscribed = 0')->fetch_assoc()['c'];
$sent_today = function_exists('brevo_sent_today') ? brevo_sent_today() : 0;
$budget = function_exists('brevo_newsletter_remaining_today') ? brevo_newsletter_remaining_today() : 0;

head('Newsletter', 'Newsletter');
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <?php if ($notice): ?>
                <div class="alert alert-<?php echo $notice[0] === 'ok' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
            <?php endif; ?>

            <p>
                <strong><?php echo $subs; ?></strong> active subscribers &middot;
                <strong><?php echo $sent_today; ?></strong> Brevo sends used today &middot;
                <strong><?php echo $budget; ?></strong> newsletter sends left today (reserve kept for transactional mail).
            </p>

            <div class="panel panel-default">
                <div class="panel-heading"><strong>New campaign</strong></div>
                <div class="panel-body">
                    <form method="post" action="/admin/newsletter">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="create">
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" name="subject" class="form-control" maxlength="255" required>
                        </div>
                        <div class="form-group">
                            <label>Body (HTML allowed)</label>
                            <textarea name="body" class="form-control" rows="10" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Create draft</button>
                    </form>
                </div>
            </div>

            <h4>Campaigns</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>#</th><th>Subject</th><th>Status</th><th>Progress</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php
                    $cs = $db->query('SELECT * FROM newsletter_campaigns ORDER BY id DESC LIMIT 50');
                    if ($cs && $cs->num_rows):
                        while ($c = $cs->fetch_assoc()):
                            $total = (int) $c['total_recipients'];
                            $done = (int) $c['sent_count'];
                    ?>
                        <tr>
                            <td><?php echo (int) $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['subject']); ?></td>
                            <td><?php echo htmlspecialchars($c['status']); ?></td>
                            <td><?php echo $total ? ($done . ' / ' . $total) : '&mdash;'; ?></td>
                            <td>
                                <?php if ($c['status'] === 'draft'): ?>
                                    <form method="post" action="/admin/newsletter" style="display:inline" onsubmit="return confirm('Queue this to all <?php echo $subs; ?> subscribers?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                        <input type="hidden" name="action" value="start">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int) $c['id']; ?>">
                                        <button class="btn btn-sm btn-success">Start sending</button>
                                    </form>
                                <?php elseif ($c['status'] === 'sending' || $c['status'] === 'paused'): ?>
                                    <form method="post" action="/admin/newsletter" style="display:inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                        <input type="hidden" name="action" value="<?php echo $c['status'] === 'sending' ? 'pause' : 'resume'; ?>">
                                        <input type="hidden" name="campaign_id" value="<?php echo (int) $c['id']; ?>">
                                        <button class="btn btn-sm btn-default"><?php echo $c['status'] === 'sending' ? 'Pause' : 'Resume'; ?></button>
                                    </form>
                                <?php else: ?>
                                    &mdash;
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-gray">No campaigns yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php
footer();
