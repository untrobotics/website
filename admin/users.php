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

?>
<html>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <style>
        th {
            font-weight: 800;
        }
        td {
            border-bottom: 1px solid #d4d4d4;
        }
        td:not(:last-child) {
            padding-right: 20px;
        }
        body {
            padding: 25px;
        }
        .alert-inline {
            display: inline-block;
        }
    </style>
</head>
<body>
    <h2 class="mb-5">UNT Robotics - Users Currently in Good Standing Report</h2>

    <div>
        <div class="alert alert-info alert-inline">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
            </svg> This is a list of everyone who has paid their dues and is currently in good standing.
        </div>
        <span>
            <a class="btn btn-primary" href="?download">Download as CSV</a>
        </span>
    </div>

    <strong style="font-size: 18px;">Viewing Term: <?php echo Semester::get_name_from_value($term); ?> - <?php echo $year; ?></strong> --
    <a href="?term=<?php echo $untrobotics->get_prev_term($term); ?>&year=<?php echo $year-1; ?>">view previous</a>, <a href="?term=<?php echo $untrobotics->get_next_term($term); ?>&year=<?php echo $year+1; ?>">view next</a>
    <br />
    Total: <?php echo $q->num_rows; ?>

    <table>
        <tr>
            <th>Name</th>
            <th>E-mail</th>
            <th>Grad. Date</th>
            <th>EUID</th>
            <th>Dues Payment Date</th>
            <th>Paypal Transaction</th>
            <th>Discord ID</th>
        </tr>
    <?php
    while ($r = $q->fetch_array(MYSQLI_ASSOC)) {
        $user = getUserInfo($r['uid']);

        // name
        // email
        // graduation date
        // euid

        ?>
            <tr>
                <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></td>
                <td><?php echo Semester::get_name_from_value($user['grad_term']); ?> - <?php echo htmlspecialchars($user['grad_year'], ENT_QUOTES); ?></td>
                <td><?php echo htmlspecialchars($user['unteuid'], ENT_QUOTES); ?></td>
                <td><?php echo admin_ts($r['payment_timestamp']); ?></td>
                <td><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=<?php echo htmlspecialchars($r['txid'], ENT_QUOTES); ?>"><?php echo htmlspecialchars($r['txid'], ENT_QUOTES); ?></a></td>
                <td><?php echo htmlspecialchars($user['discord_id'], ENT_QUOTES); ?></td>
            </tr>
        <?php
    }
    ?></table>
<?php admin_ts_script(); ?>
</body>
</html>