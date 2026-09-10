<?php
require("../template/top.php");

if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$cur_term = (int) $untrobotics->get_current_term();
$cur_year = (int) $untrobotics->get_current_year();
$term = isset($_POST['term']) ? (int) $_POST['term'] : $cur_term;
$year = isset($_POST['year']) ? (int) $_POST['year'] : $cur_year;

$notice = null;
$results = null;

/** Pull an email + any EUID-looking tokens out of one pasted line. */
function parse_line($line) {
    $email = null;
    if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $line, $m)) {
        $email = strtolower($m[0]);
    }
    // EUID tokens: letters+digits (e.g. abc0123), excluding the email.
    $euids = array();
    $stripped = $email ? str_ireplace($email, ' ', $line) : $line;
    foreach (preg_split('/[\s,;|]+/', $stripped) as $tok) {
        $tok = trim($tok);
        if ($tok !== '' && preg_match('/^[a-z]{2,4}\d{3,5}$/i', $tok)) {
            $euids[] = strtolower($tok);
        }
    }
    return array($email, $euids);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($csrf, $_POST['csrf_token'])) {
        $notice = array('err', 'Session expired. Please retry.');
    } else {
        $sheet = isset($_POST['sheet']) ? (string) $_POST['sheet'] : '';
        // Parse the pasted bookkeeping sheet into sets of emails + EUIDs.
        $sheet_emails = array();
        $sheet_euids = array();
        $sheet_rows = 0;
        foreach (preg_split('/\r\n|\r|\n/', $sheet) as $line) {
            if (trim($line) === '') { continue; }
            $sheet_rows++;
            list($e, $eu) = parse_line($line);
            if ($e) { $sheet_emails[$e] = true; }
            foreach ($eu as $u) { $sheet_euids[$u] = true; }
        }

        // Site side: non-refunded dues payments for the chosen term/year.
        $paid = array();
        $st = $db->prepare('SELECT name, email, euid, amount FROM dues_payments WHERE dues_term = ? AND dues_year = ? AND refunded = 0');
        $st->bind_param('ii', $term, $year);
        $st->execute();
        $res = $st->get_result();
        while ($res && ($r = $res->fetch_assoc())) {
            $paid[] = $r;
        }

        // Diff.
        $on_site_not_in_sheet = array();
        foreach ($paid as $r) {
            $em = strtolower(trim((string) $r['email']));
            $eu = strtolower(trim((string) $r['euid']));
            $in_sheet = ($em !== '' && isset($sheet_emails[$em])) || ($eu !== '' && isset($sheet_euids[$eu]));
            if (!$in_sheet) { $on_site_not_in_sheet[] = $r; }
        }
        // Sheet entries not matched to any site payment.
        $site_emails = array();
        $site_euids = array();
        foreach ($paid as $r) {
            $em = strtolower(trim((string) $r['email'])); if ($em !== '') { $site_emails[$em] = true; }
            $eu = strtolower(trim((string) $r['euid'])); if ($eu !== '') { $site_euids[$eu] = true; }
        }
        $in_sheet_not_on_site = array();
        foreach ($sheet_emails as $e => $_) {
            if (!isset($site_emails[$e])) { $in_sheet_not_on_site[] = $e; }
        }
        foreach ($sheet_euids as $u => $_) {
            if (!isset($site_euids[$u]) && !in_array($u, $in_sheet_not_on_site, true)) {
                // only list an EUID row if it didn't already come in via its email
                $in_sheet_not_on_site[] = $u;
            }
        }

        $matched = count($paid) - count($on_site_not_in_sheet);
        $results = array(
            'sheet_rows' => $sheet_rows,
            'paid_count' => count($paid),
            'matched' => $matched,
            'on_site_not_in_sheet' => $on_site_not_in_sheet,
            'in_sheet_not_on_site' => $in_sheet_not_on_site,
        );
    }
}

head('Dues Verify', 'Dues Verify');
require_once(BASE . '/admin/_styles.php');
?>
<main id="main-content" class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>Dues Verify</h1>
                    <p class="lead">Reconcile the treasurer's bookkeeping sheet against recorded dues payments &mdash; paste the sheet, and this flags who's in one but not the other for the selected term.</p>
                </div>

                <?php if ($notice): ?>
                    <div class="admin-notice <?php echo $notice[0] === 'ok' ? 'ok' : 'err'; ?>"><?php echo htmlspecialchars($notice[1]); ?></div>
                <?php endif; ?>

                <div class="admin-help">
                    <strong>How it works</strong>
                    <ul>
                        <li>Paste the bookkeeping sheet below &mdash; one member per line. Any line containing an <strong>email</strong> or a <strong>UNT EUID</strong> (e.g. <code>abc0123</code>) is matched; extra columns (name, amount) are ignored.</li>
                        <li>Matching is by email or EUID against non-refunded <code>dues_payments</code> for the chosen term/year.</li>
                        <li>You get two discrepancy lists: <em>recorded on the site but not in your sheet</em>, and <em>in your sheet but not recorded on the site</em>.</li>
                    </ul>
                </div>

                <div class="admin-card">
                    <div class="bd">
                        <form method="post" action="/admin/dues-verify" class="admin-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                            <div class="row-inline">
                                <div class="fld">
                                    <label for="term">Term</label>
                                    <select id="term" name="term">
                                        <?php foreach (array(0 => 'Spring', 1 => 'Autumn', 2 => 'Summer') as $tv => $tl): ?>
                                            <option value="<?php echo $tv; ?>" <?php echo $term === $tv ? 'selected' : ''; ?>><?php echo $tl; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="fld">
                                    <label for="year">Year</label>
                                    <input id="year" type="number" name="year" value="<?php echo $year; ?>" min="2018" max="2100">
                                </div>
                            </div>
                            <div class="fld">
                                <label for="sheet">Bookkeeping sheet (paste rows)</label>
                                <textarea id="sheet" name="sheet" rows="10" placeholder="jane.doe@my.unt.edu&#10;jrd0234, John Doe, 20.00&#10;..."><?php echo isset($_POST['sheet']) ? htmlspecialchars($_POST['sheet']) : ''; ?></textarea>
                            </div>
                            <button class="btn-solid go">Reconcile</button>
                        </form>
                    </div>
                </div>

                <?php if ($results !== null): ?>
                    <div class="admin-stats">
                        <?php echo admin_stat($results['sheet_rows'], 'Sheet rows'); ?>
                        <?php echo admin_stat($results['paid_count'], 'Recorded (site)'); ?>
                        <?php echo admin_stat($results['matched'], 'Matched', 'green'); ?>
                        <?php echo admin_stat(count($results['on_site_not_in_sheet']) + count($results['in_sheet_not_on_site']), 'Discrepancies', (count($results['on_site_not_in_sheet']) + count($results['in_sheet_not_on_site'])) ? 'amber' : 'green'); ?>
                    </div>

                    <div class="admin-section-title">Recorded on the site but NOT in your sheet (<?php echo count($results['on_site_not_in_sheet']); ?>)</div>
                    <div class="admin-card"><div class="admin-table-wrap"><table class="admin-table">
                        <thead><tr><th>Name</th><th>Email</th><th>EUID</th><th>Amount</th></tr></thead>
                        <tbody>
                        <?php if ($results['on_site_not_in_sheet']): foreach ($results['on_site_not_in_sheet'] as $r): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['name']); ?></td>
                                <td><?php echo htmlspecialchars($r['email']); ?></td>
                                <td><?php echo htmlspecialchars($r['euid']); ?></td>
                                <td class="num">$<?php echo htmlspecialchars(number_format((float) $r['amount'], 2)); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4" class="admin-empty">None &mdash; every recorded payment is in your sheet.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table></div></div>

                    <div class="admin-section-title">In your sheet but NOT recorded on the site (<?php echo count($results['in_sheet_not_on_site']); ?>)</div>
                    <div class="admin-card"><div class="admin-table-wrap"><table class="admin-table">
                        <thead><tr><th>Email / EUID from your sheet</th></tr></thead>
                        <tbody>
                        <?php if ($results['in_sheet_not_on_site']): foreach ($results['in_sheet_not_on_site'] as $x): ?>
                            <tr><td><?php echo htmlspecialchars($x); ?></td></tr>
                        <?php endforeach; else: ?>
                            <tr><td class="admin-empty">None &mdash; every sheet entry has a recorded payment.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table></div></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>
<?php
footer();
