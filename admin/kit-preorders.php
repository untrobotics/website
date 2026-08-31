<?php
require("../template/top.php");
require(BASE . '/api/discord/bots/admin.php');

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
                            . '<p>Good news &mdash; your <strong>Electronics Kit</strong> is assembled and ready to pick up! &#129302;</p>'
                            . '<p>Kits are handed out in person at our <strong>general meetings</strong>. Check the '
                            . '<a href="https://www.untrobotics.com/events">event calendar</a> and our '
                            . '<a href="https://www.untrobotics.com/join/discord">Discord</a> for the next meeting &mdash; the robotics office is <strong>C119</strong>.</p>'
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
$stats = $db->query('SELECT COUNT(*) total, SUM(status = "paid" AND refunded = 0) paid, SUM(status = "ready") ready, SUM(status = "picked_up") picked_up FROM kit_preorders')->fetch_assoc();
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <?php if ($notice): ?>
                <div class="alert alert-<?php echo $notice[0] === 'ok' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
            <?php endif; ?>

            <h3>Electronics Kit preorders</h3>
            <p class="text-gray">
                <?php echo (int) $stats['total']; ?> total &mdash;
                <strong><?php echo (int) $stats['paid']; ?></strong> awaiting build,
                <strong><?php echo (int) $stats['ready']; ?></strong> ready,
                <strong><?php echo (int) $stats['picked_up']; ?></strong> picked up.
            </p>

            <div class="table-responsive">
                <table class="table">
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
                        <tr<?php echo $row['refunded'] ? ' class="text-gray"' : ''; ?>>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><?php echo htmlspecialchars(trim($row['first_name'] . ' ' . $row['last_name'])); ?></td>
                            <td><a href="tel:<?php echo htmlspecialchars($phd); ?>"><?php echo htmlspecialchars($phDisp); ?></a></td>
                            <td><?php echo htmlspecialchars($row['email'] ? $row['email'] : '—'); ?></td>
                            <td>$<?php echo htmlspecialchars(number_format((float) $row['amount'], 2)); ?><br><span class="text-gray"><?php echo htmlspecialchars(date('M j, g:ia', strtotime($row['ordered_at']))); ?></span></td>
                            <td><?php echo htmlspecialchars($st); ?></td>
                            <td>
                                <?php if (!$row['refunded'] && $row['status'] === 'paid'): ?>
                                <form method="post" action="/admin/kit-preorders" style="display:inline" onsubmit="return confirm('Mark ready and email the buyer that their kit is ready for pickup?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="action" value="mark_ready">
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <button class="btn btn-sm btn-success"><?php echo $row['email'] ? 'Mark ready &amp; email' : 'Mark ready (call them)'; ?></button>
                                </form>
                                <?php elseif (!$row['refunded'] && $row['status'] === 'ready'): ?>
                                <form method="post" action="/admin/kit-preorders" style="display:inline">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                    <input type="hidden" name="action" value="mark_picked_up">
                                    <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                    <button class="btn btn-sm btn-default">Mark picked up</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-gray">No preorders yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</main>
<?php
footer();
