<?php
require("../template/top.php");
require(BASE . '/api/discord/bots/admin.php');
require_once(BASE . '/admin/_timestamps.php');

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
        $id = (int) @$_POST['id'];
        if ($id > 0 && ($action === 'mark_ready' || $action === 'mark_picked_up')) {
            $r = $db->query('SELECT first_name, email, status FROM kit_preorders WHERE id = ' . $id . ' AND refunded = 0 LIMIT 1');
            if ($r && $r->num_rows) {
                $row = $r->fetch_assoc();
                if ($action === 'mark_ready') {
                    $db->query('UPDATE kit_preorders SET status = "ready", ready_at = NOW() WHERE id = ' . $id);
                    $emailed = 'no email on file';
                    if (!empty($row['email'])) {
                        $sent = email(
                            $row['email'],
                            'Your UNT Robotics Electronics Kit is ready for pickup',
                            '<p>Hi ' . htmlspecialchars($row['first_name'] !== '' ? $row['first_name'] : 'there') . ',</p>'
                            . '<p>Good news! Your <strong>Electronics Kit</strong> is assembled and ready to pick up. &#129302;</p>'
                            . '<p>Kits are handed out in person at our <strong>general meetings</strong>. Check the '
                            . '<a href="https://www.untrobotics.com/events">event calendar</a> and our '
                            . '<a href="https://www.untrobotics.com/join/discord">Discord</a> for the next meeting. General meetings are held in room <strong>B185</strong>.</p>'
                            . '<p>See you there! Questions? <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>.</p>'
                        );
                        $emailed = $sent ? 'pickup email sent' : 'email send FAILED';
                    }
                    $notice = array('ok', "Marked #{$id} ready ({$emailed}).");
                } else {
                    $db->query('UPDATE kit_preorders SET status = "picked_up" WHERE id = ' . $id);
                    $notice = array('ok', "Marked #{$id} picked up.");
                }
            } else {
                $notice = array('error', 'Preorder not found.');
            }
        }
    }
}

head('Kit Preorders', 'Kit Preorders');
require_once(BASE . '/admin/_styles.php');
$stats = $db->query('SELECT COUNT(*) total, SUM(status = "paid" AND refunded = 0) paid, SUM(status = "ready") ready, SUM(status = "picked_up") picked_up FROM kit_preorders')->fetch_assoc();
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Electronics Kit preorders</h1>
                    <p class="lead">Electronics Kit preorders and their status. Mark each one ready once it's built, then picked up when the member collects it.</p>
                </div>

                <?php if ($notice): ?>
                    <div class="admin-notice <?php echo $notice[0] === 'ok' ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
                <?php endif; ?>

                <div class="admin-stats">
                    <?php
                    echo admin_stat((int) $stats['total'], 'Total preorders', 'grey');
                    echo admin_stat((int) $stats['paid'], 'Awaiting build', 'amber');
                    echo admin_stat((int) $stats['ready'], 'Ready for pickup', 'green');
                    echo admin_stat((int) $stats['picked_up'], 'Picked up', 'grey');
                    ?>
                </div>

                <div class="admin-help">
                    <strong>What the buttons do</strong>
                    <ul>
                        <li><strong>Mark ready &amp; email</strong> sets the preorder to <?php echo admin_pill('ready'); ?> and emails the buyer that their kit is ready to collect at a general meeting (room B185). With no email on file the button reads <em>Mark ready (call them)</em> and just changes the status, so reach out by phone.</li>
                        <li><strong>Mark picked up</strong> records that the member collected their kit and moves it to <?php echo admin_pill('picked_up'); ?>.</li>
                        <li><?php echo admin_pill('refunded'); ?> preorders are dimmed and have no actions.</li>
                    </ul>
                </div>

                <div class="admin-card">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>#</th><th>Name</th><th>Phone</th><th>Email</th><th>Paid</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php
                            $rows = $db->query('SELECT * FROM kit_preorders ORDER BY id DESC');
                            if ($rows && $rows->num_rows):
                                while ($row = $rows->fetch_assoc()):
                                    $st = $row['refunded'] ? 'refunded' : $row['status'];
                                    $phd = preg_replace('/\D/', '', (string) $row['phone']);
                                    $phDisp = strlen($phd) === 10 ? '(' . substr($phd, 0, 3) . ') ' . substr($phd, 3, 3) . '-' . substr($phd, 6) : $row['phone'];
                            ?>
                                <tr<?php echo $row['refunded'] ? ' class="is-dim"' : ''; ?>>
                                    <td class="num"><?php echo (int) $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])); ?></td>
                                    <td class="num"><a href="tel:<?php echo htmlspecialchars($phd); ?>"><?php echo htmlspecialchars($phDisp); ?></a></td>
                                    <td><?php echo $row['email'] ? htmlspecialchars($row['email']) : '<span class="muted">no email</span>'; ?></td>
                                    <td class="num">$<?php echo htmlspecialchars(number_format((float) $row['amount'], 2)); ?><br><span class="muted"><?php echo admin_ts($row['ordered_at'], 'M j, g:ia'); ?></span></td>
                                    <td><?php echo admin_pill($st); ?></td>
                                    <td>
                                        <div class="admin-actions">
                                        <?php if (!$row['refunded'] && $row['status'] === 'paid'): ?>
                                        <form method="post" action="/admin/kit-preorders" onsubmit="return confirm('Mark ready and email the buyer that their kit is ready for pickup?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="mark_ready">
                                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                            <button class="btn-pill go"><?php echo $row['email'] ? 'Mark ready &amp; email' : 'Mark ready (call them)'; ?></button>
                                        </form>
                                        <?php elseif (!$row['refunded'] && $row['status'] === 'ready'): ?>
                                        <form method="post" action="/admin/kit-preorders">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="mark_picked_up">
                                            <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                            <button class="btn-pill neutral">Mark picked up</button>
                                        </form>
                                        <?php else: ?>
                                            <span class="muted">-</span>
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="7" class="admin-empty">No preorders yet.</td></tr>
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
