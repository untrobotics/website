<?php
require('../template/top.php');

function handleUserDeletion($request) {
    global $db;

    $id = $request['id'];

    $q = $db->query('SELECT * FROM ftpusers WHERE id = "' . $db->real_escape_string($id) . '"');

    $user = $q->fetch_assoc();
    $username = $user['name'];

    unlink(FTP_USER_CONFIG_DIR . $username);

    $db->query('DELETE FROM ftpusers WHERE id = "' . $db->real_escape_string($id) . '"');

    return true;
}

$success = false;
$error = false;

if (isset($_POST) && count($_POST)) {

    do {
        if (isset($_POST['action'])){

            $action = $_POST['action'];

            switch ($action) {
                case "Delete":
                    if (handleUserDeletion($_POST)) {
                        $success = "User deleted";
                    }
                    break;
                default:
                    $error = "Unrecognised action";
                    break;
            }
        } else {

            $email = null;
            if (isset($_POST['email'])) {
                $email = $_POST['email'];

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email address";
                    break;
                }

                $token = bin2hex(random_bytes(16));
                $link = "https://www.untrobotics.com/webmasters/ftp/register?token={$token}";

                $q = $db->query("
                    INSERT INTO ftpinvites
                    
                    (email, registration_token)
                    VALUES
                    (
                     '" . $db->real_escape_string($email) . "',
                     '" . $db->real_escape_string($token) . "'
                    )
                ");

                if ($q) {
                    email($email, "UNT Robotics FTP Access Registration",
                        "Howdy, if you've received this e-mail, you already know what it's about: $link");

                    $success = "User invited";
                } else {
                    $error = "Failed to invite user due to DB insertion error:" . $db->error;
                }
            }

//            $q = $db->query('INSERT INTO ftpusers (name, passwd)
//		VALUES (
//			"' . $db->real_escape_string($username) . '",
//		    PASSWORD("' . $db->real_escape_string($password) . '")
//		)');
//t
//            copy(FTP_USER_CONFIG_FILE, FTP_USER_CONFIG_DIR . $username);
//
//            if ($q) {
//                echo 'ADD_SUCCESS';
//            } else {
//                echo 'ERROR';
//            }
        }

    } while (false);
}

$q = $db->query("SELECT * FROM ftpusers");

?>

<html>
<head>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <style>
        body {
            padding: 25px;
        }
        .ftp-user-table td {
            border-top: 1px solid #a9a9a9;
            padding: 5px;
        }
    </style>
</head>
<body>
    <main class="page-content">
        <section class="section-50 section-md-75 section-md-100 section-lg-120 section-xl-150 bg-wild-sand">
            <div class="shell text-left">
                <h2>Invite FTP user</h2>

                <?php if ($error) { ?>
                    <div class="alert alert-danger alert-inline">
                        <?php echo $error; ?>
                    </div>
                <?php } ?>

                <?php if ($success) { ?>
                    <div class="alert alert-success alert-inline">
                        <?php echo $success; ?>
                    </div>
                <?php } ?>

                <form action="" method="post">
                    <div class="range offset-top-40 offset-md-top-120">
                        <div class="cell-lg-4 cell-md-6">
                            <div class="form-group postfix-xl-right-40">
                                <label for="email" class="form-label">Email address *</label>
                                <input id="email" type="text" name="email" data-constraints="@Required" class="form-control">
                            </div>
                        </div>
                    </div>

                    <span>
                        <input type="submit" value="Invite User" class="btn btn-primary" />
                    </span>

                </form>

                <strong>Total: <?php echo $q->num_rows; ?></strong>

                <table class="ftp-user-table">
                    <tr>
                        <th>ID</th>
                        <th>Login Username</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    while ($r = $q->fetch_array(MYSQLI_ASSOC)) {
                        ?>
                        <form action="" method="post">
                        <tr>
                            <td><?php echo $r['id']; ?></td>
                            <td><strong><?php echo $r['name']; ?></strong></td>
                            <td>
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>" class="btn btn-danger" />
                                <input type="submit" name="action" value="Delete" class="btn btn-danger" />
                            </td>
                        </tr>
                        </form>
                        <?php
                    }
                    ?></table>
            </div>
        </section>
    </main>
</body>
</html>
