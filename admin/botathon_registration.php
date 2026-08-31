<?php

require('../template/top.php');

// Admin-only: dumps every registrant's PII (name, email, phone, major, etc.).
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

$q = $db->query('SELECT * FROM botathon_registration WHERE season = "' . $db->real_escape_string(BOTATHON_SEASON) . '"');

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
                    <p class="lead">Everyone signed up for the current Botathon season. Registrations from previous seasons are hidden.</p>
                </div>

                <div class="admin-stats">
                    <?php
                    echo admin_stat($count, 'Registrants', 'green');
                    echo admin_stat('#' . BOTATHON_SEASON, 'Season', 'grey');
                    ?>
                </div>

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
                                <tr><td colspan="10" class="admin-empty">No registrations for the current season yet.</td></tr>
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
