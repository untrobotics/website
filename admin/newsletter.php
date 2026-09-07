<?php
require("../template/top.php");
require_once(BASE . '/admin/_timestamps.php');

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
        $notice = array('error', 'Session expired. Please retry.');
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
                $notice = array('ok', "Started. {$total} recipients queued. Sends go out within the daily limit.");
            } else {
                $notice = array('error', 'Campaign is not a draft.');
            }
        } elseif ($action === 'test') {
            $cid = (int) @$_POST['campaign_id'];
            $c = $db->query('SELECT subject, body FROM newsletter_campaigns WHERE id = "' . $db->real_escape_string($cid) . '"');
            if ($c && $c->num_rows) {
                $cc = $c->fetch_assoc();
                $to = $userinfo['email'];
                $body = $cc['body'] . newsletter_unsub_footer($to);
                // $archive=false to mirror a real newsletter send; only goes to the admin.
                $sent_ok = email($to, '[TEST] ' . $cc['subject'], $body, false, null, array(), true, false);
                $notice = $sent_ok
                    ? array('ok', 'Test sent to ' . $to . '. Check the formatting before you start sending.')
                    : array('error', 'Test send failed. Check the mail logs.');
            } else {
                $notice = array('error', 'Campaign not found.');
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
require_once(BASE . '/admin/_styles.php');
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Newsletter</h1>
                    <p class="lead">Compose a campaign, send yourself a test, then start it. Sends go out gradually within the daily limit, with room kept for transactional mail.</p>
                </div>

                <?php if ($notice): ?>
                    <div class="admin-notice <?php echo $notice[0] === 'ok' ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
                <?php endif; ?>

                <div class="admin-stats">
                    <?php
                    echo admin_stat($subs, 'Active subscribers', 'green');
                    echo admin_stat($sent_today, 'Brevo sends used today', 'grey');
                    echo admin_stat($budget, 'Newsletter sends left today', 'amber');
                    ?>
                </div>

                <div class="admin-help">
                    <strong>How sending works</strong>
                    <ul>
                        <li><strong>Create draft</strong> saves the campaign without sending anything.</li>
                        <li><strong>Send test to me</strong> emails just you a copy so you can check formatting.</li>
                        <li><strong>Start sending</strong> queues every active subscriber; delivery goes out within the daily budget above. You can <strong>Pause</strong> or <strong>Resume</strong> a send at any time.</li>
                    </ul>
                </div>

                <div class="admin-card">
                    <div class="hd">New campaign</div>
                    <div class="bd">
                        <form method="post" action="/admin/newsletter" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="action" value="create">
                            <div class="fld">
                                <label for="nl-subject">Subject</label>
                                <input id="nl-subject" type="text" name="subject" maxlength="255" required>
                            </div>
                            <div class="fld">
                                <label for="nl-body">Body <span class="muted">(HTML allowed)</span></label>
                                <textarea id="nl-body" name="body" rows="10" required></textarea>
                            </div>
                            <button type="submit" class="btn-solid primary">Create draft</button>
                        </form>
                    </div>
                </div>

                <div class="admin-section-title">Campaigns</div>
                <div class="admin-card">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>#</th><th>Subject</th><th>Status</th><th>Progress</th><th>Created</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php
                            $cs = $db->query('SELECT * FROM newsletter_campaigns ORDER BY id DESC LIMIT 50');
                            if ($cs && $cs->num_rows):
                                while ($c = $cs->fetch_assoc()):
                                    $total = (int) $c['total_recipients'];
                                    $done = (int) $c['sent_count'];
                            ?>
                                <tr>
                                    <td class="num"><?php echo (int) $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['subject']); ?></td>
                                    <td><?php echo admin_pill($c['status']); ?></td>
                                    <td class="num"><?php echo $total ? ($done . ' / ' . $total) : '<span class="muted">-</span>'; ?></td>
                                    <td><?php echo isset($c['created_at']) ? admin_ts($c['created_at']) : '<span class="muted">-</span>'; ?></td>
                                    <td>
                                        <div class="admin-actions">
                                        <?php if ($c['status'] === 'draft'): ?>
                                            <form method="post" action="/admin/newsletter">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="test">
                                                <input type="hidden" name="campaign_id" value="<?php echo (int) $c['id']; ?>">
                                                <button class="btn-pill neutral">Send test to me</button>
                                            </form>
                                            <form method="post" action="/admin/newsletter" onsubmit="return confirm('Queue this to all <?php echo $subs; ?> subscribers?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="start">
                                                <input type="hidden" name="campaign_id" value="<?php echo (int) $c['id']; ?>">
                                                <button class="btn-pill go">Start sending</button>
                                            </form>
                                        <?php elseif ($c['status'] === 'sending' || $c['status'] === 'paused'): ?>
                                            <form method="post" action="/admin/newsletter">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="<?php echo $c['status'] === 'sending' ? 'pause' : 'resume'; ?>">
                                                <input type="hidden" name="campaign_id" value="<?php echo (int) $c['id']; ?>">
                                                <button class="btn-pill neutral"><?php echo $c['status'] === 'sending' ? 'Pause' : 'Resume'; ?></button>
                                            </form>
                                        <?php else: ?>
                                            <span class="muted">-</span>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" class="admin-empty">No campaigns yet.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
admin_ts_script();
footer();
