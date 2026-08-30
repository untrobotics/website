<?php
require("../template/top.php");

if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

head('Admin', 'Admin');

$tools = array(
    array('/admin/newsletter', 'Newsletter', 'Compose and send the email newsletter (drips out within the daily limit).'),
    array('/admin/dues-requests', 'Dues Requests', 'Approve alternative-dues requests + mark members paid (in-person / manual).'),
    array('/admin/kit-preorders', 'Kit Preorders', 'Robot Car Kit preorders — track who paid + mark ready & email pickup notices.'),
    array('/admin/users', 'Users', 'Member list + Good Standing CSV export.'),
    array('/admin/check-good-standing', 'Check Good Standing', 'Look up a single member\'s standing by UID.'),
    array('/admin/botathon_registration', 'Botathon Registrations', 'Botathon sign-ups.'),
);
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="range">
                <?php foreach ($tools as $t): ?>
                    <div class="cell-md-6 cell-lg-4 offset-bottom-30">
                        <div class="panel panel-default" style="height:100%;">
                            <div class="panel-body">
                                <h5 style="margin-top:0;"><a href="<?php echo htmlspecialchars($t[0]); ?>"><?php echo htmlspecialchars($t[1]); ?></a></h5>
                                <p class="text-gray" style="margin:0;"><?php echo htmlspecialchars($t[2]); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<?php
footer();
