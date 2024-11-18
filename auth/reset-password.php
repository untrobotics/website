<?php
require("../template/top.php");
require("../api/discord/bots/admin.php");
if (isset($_GET['token'])) {
    do {
        $token = $_GET['token'];

        $q = $db->query("SELECT * FROM `password_reset_tokens` WHERE
                                            `token` = '" . $db->real_escape_string($token) . "' AND
                                            `expires` >= NOW()
                                             LIMIT 1");
        if ($q->num_rows == 0) {
            $error = "This password reset link is not valid or has already expired.";
            break;
        } else if (isset($_POST['email'])) {
            $r = $q->fetch_array(MYSQLI_ASSOC);
            $q = $db->query("SELECT * FROM users WHERE id = '" . $db->real_escape_string($r['uid']) . "' LIMIT 1");

            $email = $_POST['email'];
            $password1 = $_POST['password1'];
            $password2 = $_POST['password2'];

            do {
                if ($q->num_rows != 1) {
                    $error = "Failed to find the user associated with this reset request.";
                    break;
                } else {
                    $r = $q->fetch_array(MYSQLI_ASSOC);
                }

                if (empty($password1)) {
                    $error = "You must enter a password";
                    break;
                } else if ($password1 !== $password2) {
                    $error = "The passwords you entered do not match";
                    break;
                } else if (strcasecmp($email, $r['email']) != 0) {
                    $error = "The e-mail address you entered is not valid for this reset link";
                    break;
                } else {
                    $hash = password_hash($password1, PASSWORD_BCRYPT, array('cost' => 12));

                    $q = $db->query("UPDATE users
                        SET password = '" . $db->real_escape_string($hash) . "'
                        WHERE id = '" . $db->real_escape_string($r['id']) . "'"
                    );

                    if ($q) {
                        $success = "Your password has been updated. You may now <a href=\"/auth/login\">login here</a>.";
                    } else {
                        $error = "Failed to update password. Please reach out to support.";
                        AdminBot::send_message("ERROR: Unable to reset password for {$r['id']} due to: $db->error. Please investigate.");
                    }
                }
            } while (false);
        }
    } while (false);
}
head('Reset Password', true, false, false, "");
?>

    <main class="page-content">
        <!-- Classic Breadcrumbs-->
        <section class="breadcrumb-classic">
            <div class="rd-parallax">
                <div data-speed="0.25" data-type="media" data-url="/images/headers/login.jpg" class="rd-parallax-layer"></div>
                <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
                    <div class="shell">
                        <ul class="list-breadcrumb">
                            <li><a href="/">Home</a></li>
                            <li><a href="/auth/">Auth</a></li>
                            <li>Reset Password
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section class="section-50">
            <div class="shell">
                <div class="range offset-top-40">
                    <div class="cell-md-12 text-center">
                        <h2>Reset Password</h2>
                        <?php
                        if (isset($error)) {
                            ?>
                            <div class="alert alert-danger inline-alert offset-top-15"><?php echo $error; ?></div>
                            <?php
                        } else if (isset($success)) {
                            ?>
                            <div class="alert alert-success inline-alert offset-top-15"><?php echo $success; ?></div>
                            <?php
                        } else {
                        ?>
                        <form class="offset-top-10" data-form-output="form-output-global" data-form-type="login" method="post" action="" class="rd-mailform text-left">
                            <center>
                                <div style="max-width: 500px;">
                                    <div class="form-group">
                                        <label for="email" class="form-label">E-mail address</label>
                                        <input id="email" type="email" name="email" data-constraints="@Required" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label for="password1" class="form-label">Password</label>
                                        <input id="password1" type="password" name="password1" data-constraints="@Required" class="form-control" autocomplete="new-password">
                                    </div>
                                    <div class="form-group">
                                        <label for="password2" class="form-label">Confirm Password</label>
                                        <input id="password2" type="password" name="password2" data-constraints="@Required" class="form-control" autocomplete="new-password">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-default offset-top-35">Submit</button>
                            </center>
                        </form>
                        <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php
footer();
?>