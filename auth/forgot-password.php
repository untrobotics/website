<?php
require("../template/top.php");
require("../api/discord/bots/admin.php");
if (isset($_POST['email'])) {
    do {
        if (!isset($_POST["email"]) || !strlen($_POST["email"]) > 0) {
            $error = "You must enter your e-mail address.";
            break;
        }
        $q = $db->query("SELECT * FROM `users` WHERE `email` = '" . $db->real_escape_string($_POST['email']) . "' LIMIT 1");
        if ($q->num_rows == 0) {
            $error = "The e-mail address you entered is not in our database.";
            break;
        } else {
            $r = $q->fetch_array(MYSQLI_ASSOC);

            $timestamp = date('l jS \of F Y h:i:s A T');

            $reset_token = bin2hex(random_bytes(40));
            $uid = $r['id'];
            $expires = date('Y-m-d H:i:s', strtotime("+2 days"));
            $ip = $_SERVER['REMOTE_ADDR'];
            $host = gethostbyaddr($_SERVER['REMOTE_ADDR']);

            // delete any previous tokens
            $db->query("DELETE FROM password_reset_tokens WHERE uid = '" . $db->real_escape_string($uid) . "'");

            $q = $db->query("INSERT INTO password_reset_tokens 
                        (`token`, `uid`, `expires`, `ip`, `host`)
                        VALUES
                        (
                             '$reset_token',
                             '$uid',
                             '$expires',
                             '$ip',
                             '$host'
                         )");

            if (!$q) {
                $error = "Unable to create reset token. Please try again later.";
                AdminBot::send_message("ERROR: Failed to reset password for $uid due to a database error ($db->error). Please investigate.");
            } else if (
                email(
                    $r['email'],
                    WEBSITE_NAME . " Password Reset",
                    "<html>
Hello {$r['name']},
<br>
<br>
We received a request for a password reset link today at {$timestamp} from $ip ($host). Your password reset link is here:<br>
<a href=\"https://" . WEBSITE_DOMAIN . "/auth/reset-password?token=$reset_token\">https://" . WEBSITE_DOMAIN . "/auth/reset-password?token=$reset_token</a>
<br>
<br>
If you did not request this password reset link, please disregard this e-email.
<br>
<br>
Best regards,
<br>" . WEBSITE_NAME . "</html>"
                )
            ) {
                $success = "Recovery instructions have been sent to your e-mail address.";
            } else {
                $error = "Unable to send e-mail, please contact support.";
            }
        }
    } while (false);
}
head('Forgot Password', true);
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
                            <li>Forgot Password
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
                        <h2>Request Reset Password Link</h2>
                        <?php
                        if (isset($error)) {
                            ?>
                            <div class="alert alert-danger inline-alert offset-top-15"><?php echo $error; ?></div>
                            <?php
                        } else if (isset($success)) {
                            ?>
                            <div class="alert alert-success inline-alert offset-top-15"><?php echo $success; ?></div>
                            <?php
                        }
                        ?>
                        <form class="offset-top-10" data-form-output="form-output-global" data-form-type="login" method="post" action="" class="rd-mailform text-left">
                            <center>
                                <div style="max-width: 500px;">
                                    <div class="form-group">
                                        <label for="email" class="form-label">E-mail address</label>
                                        <input id="email" type="email" name="email" data-constraints="@Required" class="form-control">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-default offset-top-35">Submit</button>

                                <p style="margin-top: 40px;"><a href='/auth/login'>Back to Login</a></p>
                            </center>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php
footer();
?>