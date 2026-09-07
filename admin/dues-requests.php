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

/**
 * Record a dues payment for the CURRENT term so the member counts as being in
 * good standing, and (if their Discord is linked) assign the Good Standing role.
 * Idempotent: skips the insert if they already have a non-refunded row this term.
 */
function record_manual_dues_payment($uid, $note) {
    global $db, $untrobotics;

    $u = $db->query('SELECT name, email, unteuid, discord_id FROM users WHERE id = "' . $db->real_escape_string($uid) . '" LIMIT 1');
    if (!$u || $u->num_rows === 0) {
        return array(false, 'No user with id ' . intval($uid) . '.');
    }
    $r = $u->fetch_assoc();
    $term = $untrobotics->get_current_term();
    $year = $untrobotics->get_current_year();

    $pc = $db->query("SELECT `value` FROM dues_config WHERE `key` = 'semester_price' LIMIT 1");
    $amount = ($pc && $pc->num_rows) ? $pc->fetch_assoc()['value'] : 0;

    $already = $db->query('SELECT id FROM dues_payments WHERE uid = "' . $db->real_escape_string($uid) . '" AND dues_term = "' . $db->real_escape_string($term) . '" AND dues_year = "' . $db->real_escape_string($year) . '" AND refunded = 0 LIMIT 1');
    if (!$already || $already->num_rows === 0) {
        $txid = 'manual-' . $uid . '-' . $term . '-' . $year . '-' . time();
        $ins = $db->query('INSERT INTO dues_payments (name, email, euid, amount, fee, txid, dues_term, dues_year, uid) VALUES ('
            . '"' . $db->real_escape_string($r['name']) . '", '
            . '"' . $db->real_escape_string($r['email']) . '", '
            . '"' . $db->real_escape_string($r['unteuid']) . '", '
            . '"' . $db->real_escape_string($amount) . '", '
            . '"0.00", '
            . '"' . $db->real_escape_string($txid) . '", '
            . '"' . $db->real_escape_string($term) . '", '
            . '"' . $db->real_escape_string($year) . '", '
            . '"' . $db->real_escape_string($uid) . '")');
        if (!$ins) {
            return array(false, 'DB error recording payment: ' . $db->error);
        }
    }

    $role = 'Discord not linked; role assigns when they visit /join/discord';
    if (!empty($r['discord_id'])) {
        try {
            AdminBot::add_user_role($r['discord_id']);
            $role = 'Good Standing role assigned';
        } catch (Exception $e) {
            $role = 'role assignment FAILED (' . $e->getMessage() . '). Assign manually';
        }
    }
    AdminBot::send_message("(Dues) {$r['name']} (uid {$uid}) marked paid for the current term via admin. {$note}");
    return array(true, "Marked {$r['name']} paid for the current term. {$role}.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $notice = array('error', 'Session expired. Please retry.');
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        if ($action === 'approve') {
            $rid = (int) @$_POST['request_id'];
            $req = $db->query('SELECT uid, reason, status FROM dues_alternative_requests WHERE id = "' . $db->real_escape_string($rid) . '" LIMIT 1');
            if ($req && $req->num_rows && $req->fetch_assoc()['status'] === 'pending') {
                $req2 = $db->query('SELECT uid, reason FROM dues_alternative_requests WHERE id = "' . $db->real_escape_string($rid) . '"')->fetch_assoc();
                list($ok, $msg) = record_manual_dues_payment($req2['uid'], 'Alternative request #' . $rid . ' (' . $req2['reason'] . ') approved.');
                if ($ok) {
                    $db->query('UPDATE dues_alternative_requests SET status = "approved", reviewed_by = "' . $db->real_escape_string($userinfo['id']) . '", reviewed_at = NOW() WHERE id = "' . $db->real_escape_string($rid) . '"');
                }
                $notice = array($ok ? 'ok' : 'error', $msg);
            } else {
                $notice = array('error', 'Request not found or already handled.');
            }
        } elseif ($action === 'deny') {
            $rid = (int) @$_POST['request_id'];
            $db->query('UPDATE dues_alternative_requests SET status = "denied", reviewed_by = "' . $db->real_escape_string($userinfo['id']) . '", reviewed_at = NOW() WHERE id = "' . $db->real_escape_string($rid) . '" AND status = "pending"');
            $notice = array('ok', 'Request denied.');
        } elseif ($action === 'mark_paid') {
            $uid = (int) @$_POST['uid'];
            if ($uid > 0) {
                list($ok, $msg) = record_manual_dues_payment($uid, 'Marked paid directly (in-person / manual).');
                $notice = array($ok ? 'ok' : 'error', $msg);
            } else {
                $notice = array('error', 'Enter a valid user id.');
            }
        }
    }
}

head('Dues Requests', 'Dues Requests');
require_once(BASE . '/admin/_styles.php');
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Dues Requests</h1>
                    <p class="lead">Approve members who asked to pay dues an alternative way, or mark a member paid directly for an in-person / manual payment.</p>
                </div>

                <?php if ($notice): ?>
                    <div class="admin-notice <?php echo $notice[0] === 'ok' ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
                <?php endif; ?>

                <div class="admin-help">
                    <strong>What these actions do</strong>
                    <ul>
                        <li><strong>Mark paid</strong> and <strong>Approve &amp; mark paid</strong> record a dues payment for the <em>current term</em>, so the member counts as in good standing, and assign the Discord Good Standing role if their account is linked (otherwise it applies when they next visit <em>/join/discord</em>). Clicking twice is safe; it won't double-record.</li>
                        <li><strong>Deny</strong> rejects the request without recording a payment.</li>
                    </ul>
                </div>

                <div class="admin-card">
                    <div class="hd">Mark a member paid <span class="sub">in-person / manual payment</span></div>
                    <div class="bd">
                        <form method="post" action="/admin/dues-requests" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="action" value="mark_paid">
                            <div class="row-inline">
                                <div class="fld">
                                    <label for="uid">User ID</label>
                                    <input id="uid" type="number" name="uid" min="1" required style="max-width:160px;">
                                </div>
                                <button class="btn-solid go" onclick="return confirm('Mark this user paid for the current term?');">Mark paid</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="admin-section-title">Pending alternative-dues requests</div>
                <div class="admin-card">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>#</th><th>Member</th><th>Reason</th><th>Requested</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            <?php
                            $rows = $db->query('SELECT r.id, r.uid, r.reason, r.created_at, u.name, u.email
                                FROM dues_alternative_requests r LEFT JOIN users u ON u.id = r.uid
                                WHERE r.status = "pending" ORDER BY r.id ASC');
                            if ($rows && $rows->num_rows):
                                while ($row = $rows->fetch_assoc()):
                            ?>
                                <tr>
                                    <td class="num"><?php echo (int) $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name'] ?: ('uid ' . $row['uid'])); ?><br><span class="muted"><?php echo htmlspecialchars($row['email']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                    <td><?php echo admin_ts($row['created_at']); ?></td>
                                    <td><?php echo admin_pill('pending'); ?></td>
                                    <td>
                                        <div class="admin-actions">
                                            <form method="post" action="/admin/dues-requests" onsubmit="return confirm('Approve and mark this member paid?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?php echo (int) $row['id']; ?>">
                                                <button class="btn-pill go">Approve &amp; mark paid</button>
                                            </form>
                                            <form method="post" action="/admin/dues-requests">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                                <input type="hidden" name="action" value="deny">
                                                <input type="hidden" name="request_id" value="<?php echo (int) $row['id']; ?>">
                                                <button class="btn-pill danger">Deny</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="6" class="admin-empty">No pending requests.</td></tr>
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
