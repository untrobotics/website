<?php

require('../template/top.php');

// Admin-only: dumps every registrant's PII (name, email, phone, major, etc.).
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];
$notice = null;

// Change the current season (DB-backed — no redeploy). New sign-ups stamp this
// season and the roster below filters to it, so old seasons drop off cleanly.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $notice = array('error', 'Session expired — please retry.');
    } elseif (($_POST['action'] ?? '') === 'set_season') {
        $new = (int) ($_POST['season'] ?? 0);
        if ($new < 1 || $new > 99) {
            $notice = array('error', 'Enter a season number between 1 and 99.');
        } elseif (setting_set('botathon_season', $new)) {
            $notice = array('ok', 'Botathon season is now #' . $new . '. New registrations and the public Botathon pages use it immediately.');
        } else {
            $notice = array('error', 'Could not save the season. Try again.');
        }
    }
}

$season = botathon_season();
$q = $db->query('SELECT * FROM botathon_registration WHERE season = "' . $db->real_escape_string($season) . '"');

head('Botathon Registrations', 'Botathon Registrations');
require_once(BASE . '/admin/_styles.php');
$count = ($q ? $q->num_rows : 0);
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Botathon Registrations</h1>
                    <p class="lead">Everyone signed up for the current Botathon season. Registrations from previous seasons are hidden &mdash; bump the season each year instead of clearing the table.</p>
                </div>

                <?php if ($notice): ?>
                    <div class="admin-notice <?php echo $notice[0] === 'ok' ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
                <?php endif; ?>

                <div class="admin-stats">
                    <?php
                    echo admin_stat($count, 'Registrants', 'green');
                    echo admin_stat('#' . $season, 'Current season', 'grey');
                    ?>
                </div>

                <div class="admin-help">
                    <strong>Season</strong> is stored in the database and controls the Botathon page title, the sign-up form, and which registrations show here. Bumping it each year means you never have to clear the registration table &mdash; old seasons simply stop appearing.
                </div>

                <div class="admin-card">
                    <div class="hd">Set current season</div>
                    <div class="bd">
                        <form method="post" action="/admin/botathon_registration" class="admin-form" onsubmit="return confirm('Set the current Botathon season? This changes the public Botathon pages immediately.');">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                            <input type="hidden" name="action" value="set_season">
                            <div class="row-inline">
                                <div class="fld">
                                    <label for="season">Season number</label>
                                    <input id="season" type="number" name="season" min="1" max="99" value="<?php echo (int) $season; ?>" required style="max-width:120px;">
                                </div>
                                <button class="btn-solid go">Save season</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="admin-section-title">Season #<?php echo (int) $season; ?> roster</div>
                <div class="admin-card">
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Gender</th><th>Classification</th><th>Major</th><th>Team</th><th>Diet</th><th>Latex allergy</th><th>EUID</th></tr></thead>
                            <tbody>
                            <?php if ($count): ?>
                                <?php while ($r = $q->fetch_array(MYSQLI_ASSOC)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r['name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['email']); ?></td>
                                        <td class="num"><?php echo htmlspecialchars($r['phone']); ?></td>
                                        <td><?php echo htmlspecialchars($r['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($r['classification']); ?></td>
                                        <td><?php echo htmlspecialchars($r['major']); ?></td>
                                        <td><?php echo $r['team_name'] ? htmlspecialchars($r['team_name']) : '<span class="muted">&mdash;</span>'; ?></td>
                                        <td><?php echo $r['diet_restrictions'] ? htmlspecialchars($r['diet_restrictions']) : '<span class="muted">none</span>'; ?></td>
                                        <td><?php echo $r['latex_allergy'] ? '<span class="pill pill-refunded">Yes</span>' : '<span class="muted">No</span>'; ?></td>
                                        <td class="num"><?php echo htmlspecialchars($r['unteuid']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="10" class="admin-empty">No registrations for season #<?php echo (int) $season; ?> yet.</td></tr>
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
footer();
