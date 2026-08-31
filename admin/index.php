<?php
require("../template/top.php");

if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

head('Admin', 'Admin');
require_once(BASE . '/admin/_styles.php');

$tools = array(
    array('/admin/finances', 'Finances', 'AR ledger across dues, donations, kits & merch — gross/fees/net by tax year, with CSV export.'),
    array('/admin/newsletter', 'Newsletter', 'Compose and send the email newsletter (drips out within the daily limit).'),
    array('/admin/emails', 'Email Log', 'Every transactional email the site has sent, with delivery status and full message bodies.'),
    array('/admin/dues-requests', 'Dues Requests', 'Approve alternative-dues requests + mark members paid (in-person / manual).'),
    array('/admin/kit-preorders', 'Kit Preorders', 'Electronics Kit preorders — track who paid + mark ready & email pickup notices.'),
    array('/admin/users', 'Users', 'Member list + Good Standing CSV export.'),
    array('/admin/check-good-standing', 'Check Good Standing', 'Look up a single member\'s standing by UID.'),
    array('/admin/botathon_registration', 'Botathon Registrations', 'Botathon sign-ups for the current season.'),
);
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <div class="admin-head">
                    <h1>Admin</h1>
                    <p class="lead">Member, dues, merch and newsletter tools. Everything here is admin-only.</p>
                </div>
                <div class="admin-hub">
                    <?php foreach ($tools as $t): ?>
                        <a class="hub-card" href="<?php echo htmlspecialchars($t[0]); ?>">
                            <div class="hc-title"><?php echo htmlspecialchars($t[1]); ?></div>
                            <p class="hc-desc"><?php echo htmlspecialchars($t[2]); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
footer();
