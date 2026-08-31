<?php
require("../template/top.php");
require_once(BASE . '/admin/_timestamps.php');

// Admin-only: the email archive contains member PII and full message bodies.
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

// sent_emails stores to/subject/message json_encode()d (legacy rows may be raw).
function email_field($raw) {
    if ($raw === null || $raw === '') { return ''; }
    $d = json_decode($raw);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (is_array($d)) { return implode(', ', array_map('strval', $d)); }
        return (string) $d;
    }
    return $raw;
}
function email_status_pill($s) {
    if ($s === null) { return '<span class="pill pill-neutral">Unknown</span>'; }
    if ((int) $s === 1) { return '<span class="pill pill-ready">Sent</span>'; }
    return '<span class="pill pill-refunded">Failed</span>';
}

head('Email Log', 'Email Log');
require_once(BASE . '/admin/_styles.php');

// ---- Detail view ----------------------------------------------------------
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $e = $db->query('SELECT * FROM sent_emails WHERE id = ' . $id . ' LIMIT 1');
    $row = ($e && $e->num_rows) ? $e->fetch_assoc() : null;
    ?>
    <main class="page-content">
        <section class="section-50">
            <div class="shell">
                <div class="admin-wrap">
                    <a class="admin-back" href="/admin/emails">&larr; Email Log</a>
                    <?php if (!$row): ?>
                        <div class="admin-notice err">No email with id <?php echo $id; ?>.</div>
                    <?php else: ?>
                        <div class="admin-head"><h1><?php echo htmlspecialchars(email_field($row['subject'])); ?></h1></div>
                        <div class="admin-card">
                            <div class="bd">
                                <table class="admin-table" style="margin-bottom:8px;">
                                    <tr><th style="width:120px;">To</th><td><?php echo htmlspecialchars(email_field($row['to'])); ?></td></tr>
                                    <tr><th>Subject</th><td><?php echo htmlspecialchars(email_field($row['subject'])); ?></td></tr>
                                    <tr><th>Status</th><td><?php echo email_status_pill($row['status']); ?></td></tr>
                                    <?php if (isset($row['created_at'])): ?><tr><th>Sent</th><td><?php echo admin_ts($row['created_at']); ?></td></tr><?php endif; ?>
                                    <?php if (!empty($row['replyto'])): ?><tr><th>Reply-To</th><td><?php echo htmlspecialchars($row['replyto']); ?></td></tr><?php endif; ?>
                                </table>
                            </div>
                        </div>
                        <div class="admin-section-title">Message</div>
                        <div class="admin-card">
                            <div class="bd flush">
                                <iframe sandbox title="email body" style="width:100%;height:640px;border:0;display:block;background:#fff;" srcdoc="<?php echo htmlspecialchars(email_field($row['message']), ENT_QUOTES); ?>"></iframe>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>
    <?php
    admin_ts_script();
    footer();
    return;
}

// ---- List view ------------------------------------------------------------
$stats = $db->query("SELECT COUNT(*) total, SUM(status = 1) sent, SUM(status = 0) failed FROM sent_emails")->fetch_assoc();
$q = trim((string) @$_GET['q']);
$per = 50;
$page = max(1, (int) @$_GET['page']);
$off = ($page - 1) * $per;
$wsql = '';
if ($q !== '') {
    $esc = $db->real_escape_string($q);
    $wsql = "WHERE `to` LIKE '%$esc%' OR subject LIKE '%$esc%'";
}
$match = (int) $db->query("SELECT COUNT(*) c FROM sent_emails $wsql")->fetch_assoc()['c'];
$pages = max(1, (int) ceil($match / $per));
$rows = $db->query("SELECT id, `to`, subject, status, created_at FROM sent_emails $wsql ORDER BY id DESC LIMIT $per OFFSET $off");
$qs = $q !== '' ? '&q=' . urlencode($q) : '';
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Email Log</h1>
                    <p class="lead">Every transactional email the site has sent &mdash; receipts, pickup notices, dues confirmations, newsletters. Click a row to read the full message.</p>
                </div>

                <div class="admin-stats">
                    <?php
                    echo admin_stat((int) $stats['total'], 'Total emails', 'grey');
                    echo admin_stat((int) $stats['sent'], 'Sent OK', 'green');
                    echo admin_stat((int) $stats['failed'], 'Failed', ((int) $stats['failed'] > 0) ? 'red' : 'grey');
                    ?>
                </div>

                <div class="admin-card">
                    <div class="hd">
                        <form method="get" action="/admin/emails" class="admin-form" style="margin:0;">
                            <div class="row-inline">
                                <div class="fld"><input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search recipient or subject" style="max-width:280px;"></div>
                                <button class="btn-pill go">Search</button>
                                <?php if ($q !== ''): ?><a class="btn-pill neutral" href="/admin/emails">Clear</a><?php endif; ?>
                            </div>
                        </form>
                        <span class="sub"><?php echo number_format($match); ?> result<?php echo $match === 1 ? '' : 's'; ?></span>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Sent</th><th>To</th><th>Subject</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                            <?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo isset($r['created_at']) ? admin_ts($r['created_at'], 'M j, Y g:ia') : ('<span class="muted">#' . (int) $r['id'] . '</span>'); ?></td>
                                    <td><?php echo htmlspecialchars(email_field($r['to'])); ?></td>
                                    <td><?php echo htmlspecialchars(email_field($r['subject'])); ?></td>
                                    <td><?php echo email_status_pill($r['status']); ?></td>
                                    <td><a class="btn-pill neutral" href="/admin/emails?id=<?php echo (int) $r['id']; ?>">View</a></td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="admin-empty">No emails<?php echo $q !== '' ? ' match that search' : ' yet'; ?>.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($pages > 1): ?>
                    <div class="bd" style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="muted">Page <?php echo $page; ?> of <?php echo $pages; ?></span>
                        <span class="admin-actions">
                            <?php if ($page > 1): ?><a class="btn-pill neutral" href="?page=<?php echo $page - 1; ?><?php echo $qs; ?>">&larr; Newer</a><?php endif; ?>
                            <?php if ($page < $pages): ?><a class="btn-pill neutral" href="?page=<?php echo $page + 1; ?><?php echo $qs; ?>">Older &rarr;</a><?php endif; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
admin_ts_script();
footer();
