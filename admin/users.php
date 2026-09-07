<?php
require("../template/top.php");
require_once(BASE . '/admin/_timestamps.php');

// Admin-only: this page exposes member PII (names, emails, phone/EUID) and a full
// CSV export. Gate BEFORE any query runs or any output/CSV is produced. auth(2)
// returns the [userinfo, session] array only for authenticated admins (is_admin = 1)
// and false otherwise; non-admins get bounced to login, matching head()'s behavior.
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

$term = @$_GET['term'];
$year = @$_GET['year'];
if (strlen($term) == 0) {
    $term = $untrobotics->get_current_term();
} else {
    $term = intval($term);
}
if (empty($year)) {
    $year = $untrobotics->get_current_year();
} else {
    $year = intval($year);
}

$q = $db->query("SELECT * FROM dues_payments WHERE dues_term = '$term' AND dues_year = '$year' ORDER BY payment_timestamp DESC");

function getUserInfo($uid) {
    global $db;
    $uq = $db->query("SELECT * FROM users WHERE id = '" . $uid. "' LIMIT 1");
    return $uq->fetch_array(MYSQLI_ASSOC);
}

if (isset($_GET['download'])) {

    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename=untrobotics-users-good-standing-report.csv");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "Name,Email,Grad Term,Grad Year,EUID,Payment Timestamp,Paypal Transaction ID,Amount Paid\n";

    while ($r = $q->fetch_array(MYSQLI_ASSOC)) {
        $user = getUserInfo($r['uid']);

        // name
        // email
        // graduation date
        // euid
        echo htmlspecialchars($user['name'], ENT_QUOTES) . "," . htmlspecialchars($user['email'], ENT_QUOTES) . "," . Semester::get_name_from_value($user['grad_term']) . "," . htmlspecialchars($user['grad_year'], ENT_QUOTES) . "," . htmlspecialchars($user['unteuid'], ENT_QUOTES) . "," . htmlspecialchars($r['payment_timestamp'], ENT_QUOTES) . "," . htmlspecialchars($r['txid'], ENT_QUOTES) . "," . htmlspecialchars($r['amount'], ENT_QUOTES) . "\n";
    }

    die();
}

head('Good Standing', 'Good Standing');
require_once(BASE . '/admin/_styles.php');
$term_label = Semester::get_name_from_value($term) . ' ' . $year;
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Members in Good Standing</h1>
                    <p class="lead">Everyone who has paid dues for the selected term and is currently in good standing. Use the CSV export for the full report.</p>
                </div>

                <div class="admin-stats">
                    <?php
                    echo admin_stat($q->num_rows, 'In good standing', 'green');
                    echo admin_stat($term_label, 'Viewing term', 'grey');
                    ?>
                </div>

                <div class="admin-card">
                    <div class="hd">
                        <span><?php echo htmlspecialchars($term_label); ?>
                            <span class="sub">&nbsp;&middot;&nbsp;
                                <a href="?term=<?php echo $untrobotics->get_prev_term($term); ?>&year=<?php echo $year - 1; ?>">&larr; previous</a>
                                &nbsp;|&nbsp;
                                <a href="?term=<?php echo $untrobotics->get_next_term($term); ?>&year=<?php echo $year + 1; ?>">next &rarr;</a>
                            </span>
                        </span>
                        <a class="btn-pill go" href="?term=<?php echo $term; ?>&year=<?php echo $year; ?>&download">Download CSV</a>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Name</th><th>Email</th><th>Grad.</th><th>EUID</th><th>Dues paid</th><th>PayPal txn</th><th>Discord</th></tr></thead>
                            <tbody>
                            <?php if ($q && $q->num_rows): ?>
                                <?php while ($r = $q->fetch_array(MYSQLI_ASSOC)):
                                    $user = getUserInfo($r['uid']); ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?></td>
                                        <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></td>
                                        <td><?php echo Semester::get_name_from_value($user['grad_term']); ?> <?php echo htmlspecialchars($user['grad_year'], ENT_QUOTES); ?></td>
                                        <td><?php echo htmlspecialchars($user['unteuid'], ENT_QUOTES); ?></td>
                                        <td><?php echo admin_ts($r['payment_timestamp']); ?></td>
                                        <td class="num"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=<?php echo htmlspecialchars($r['txid'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($r['txid'], ENT_QUOTES); ?></a></td>
                                        <td class="num"><?php echo htmlspecialchars($user['discord_id'], ENT_QUOTES); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="admin-empty">No members in good standing for <?php echo htmlspecialchars($term_label); ?>.</td></tr>
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
