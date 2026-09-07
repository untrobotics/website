<?php
require("../template/top.php");

// Admin-only: exposes any member's standing by UID (membership enumeration).
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

$u = @$_GET['u'];
$result = null;   // null = no lookup yet; array(ok, message)
if ($u !== null && $u !== '') {
    if (!is_numeric($u)) {
        $result = array(false, 'Invalid UID. Enter a numeric member id.');
    } else {
        $good = $untrobotics->is_user_in_good_standing((int) $u);
        $result = array($good, 'User #' . (int) $u . ' is ' . ($good ? '' : 'NOT ') . 'in good standing.');
    }
}

head('Check Good Standing', 'Check Good Standing');
require_once(BASE . '/admin/_styles.php');
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Check Good Standing</h1>
                    <p class="lead">Look up whether a single member is currently in good standing by their user id.</p>
                </div>

                <div class="admin-card">
                    <div class="bd">
                        <form method="get" action="/admin/check-good-standing" class="admin-form">
                            <div class="row-inline">
                                <div class="fld">
                                    <label for="u">User ID</label>
                                    <input id="u" type="number" name="u" min="1" required value="<?php echo is_numeric($u) ? (int) $u : ''; ?>" style="max-width:160px;">
                                </div>
                                <button class="btn-solid go">Check</button>
                            </div>
                        </form>

                        <?php if ($result !== null): ?>
                            <div class="admin-notice <?php echo $result[0] ? 'ok' : 'err'; ?>" style="margin:18px 0 0;">
                                <?php echo htmlspecialchars($result[1]); ?>
                                <?php echo $result[0] ? '&#128522;' : '&#128533;'; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
footer();
