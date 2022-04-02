<?php
require("../template/top.php");

$term = $untrobotics->get_current_term();
$year = $untrobotics->get_current_year();

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

    while ($r = $q->fetch_array(MYSQLI_ASSOC)) {
        $user = getUserInfo($r['uid']);

        // name
        // email
        // graduation date
        // euid

        echo "{$user['name']},{$user['email']}," . Semester::get_name_from_value($user['grad_term']) . ",{$user['grad_year']},{$user['unteuid']},{$r['payment_timestamp']},{$r['txid']}\n";
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

    <strong>Total: <?php echo $q->num_rows; ?></strong>

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
                <td><?php echo $user['name']; ?></td>
                <td><?php echo $user['email']; ?></td>
                <td><?php echo Semester::get_name_from_value($user['grad_term']); ?> - <?php echo $user['grad_year']; ?></td>
                <td><?php echo $user['unteuid']; ?></td>
                <td><?php echo $r['payment_timestamp']; ?></td>
                <td><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=<?php echo $r['txid']; ?>"><?php echo $r['txid']; ?></a></td>
                <td><?php echo $user['discord_id']; ?></td>
            </tr>
        <?php
    }
    ?></table>
</body>
</html>