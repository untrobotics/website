<?php
require("../template/top.php");
require_once(BASE . '/admin/_timestamps.php');

// Admin-only: exposes all revenue (amounts, fees, payer names, txids).
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

// ---- Period ---------------------------------------------------------------
// Tax year = calendar year (UTC). ?year=all shows everything; ?year=YYYY a year.
// Default to all-time so the page lands on the full picture (dues history runs
// back years); the year links drill into a specific tax year for filing.
$year_param = isset($_GET['year']) ? $_GET['year'] : 'all';
$all_time = ($year_param === 'all');
$year = $all_time ? null : (int) $year_param;
if (!$all_time && ($year < 2000 || $year > 2100)) {
    $year = (int) $untrobotics->get_current_year();
}
$where = '';
if (!$all_time) {
    $start = sprintf('%04d-01-01 00:00:00', $year);
    $end   = sprintf('%04d-01-01 00:00:00', $year + 1);
    $where = "WHERE ts >= '$start' AND ts < '$end'";
}

// ---- The unified ledger query --------------------------------------------
// Each stream normalised to (src, ts, descr, amount, fee, txid, refunded,
// refunded_at). Merch amount/fee come from printful_order_tx (NULL for orders
// recorded before those columns existed, surfaced as "unrecorded" below).
$sql = "SELECT src, ts, descr, amount, fee, txid, refunded, refunded_at FROM (
    SELECT 'Dues' src, payment_timestamp ts, COALESCE(name,'') descr, amount, fee, txid, refunded, refunded_at FROM dues_payments
    UNION ALL
    SELECT 'Donation', donated_at, COALESCE(name,''), amount, fee, txid, refunded, refunded_at FROM donations
    UNION ALL
    SELECT 'Kit', ordered_at, TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))), amount, fee, txid, refunded, refunded_at FROM kit_preorders
    UNION ALL
    SELECT 'Merch', t.`timestamp`, COALESCE(NULLIF(TRIM(CONCAT(COALESCE(o.order_name,''),' ',COALESCE(o.order_variant_name,''))),''),'Merch order'), t.amount, t.fee, t.txid, COALESCE(o.refunded,0), o.refunded_at
        FROM printful_order_tx t LEFT JOIN printful_order o ON o.order_id = t.printful_order_id
) led
$where
ORDER BY ts DESC";

$rows = $db->query($sql);

/** Processor a txid came through, for the ledger's Processor column. */
function ledger_processor($txid) {
    $txid = (string) $txid;
    if (strpos($txid, 'manual-') === 0) { return 'Manual'; }
    // Stripe ids: cs_ (Checkout Session), pi_ (PaymentIntent), ch_/py_ (charge), etc.
    if (preg_match('/^(cs_|pi_|ch_|py_|in_|txn_|re_|seti_)/', $txid)) { return 'Stripe'; }
    if ($txid === '') { return 'Unknown'; }
    return 'PayPal';
}

// ---- Roll-up + collect rows ----------------------------------------------
$ledger = array();
$gross = 0.0; $fees = 0.0; $refunded_amt = 0.0; $refunded_n = 0; $unrecorded = 0;
$by_type = array('Dues' => array('n' => 0, 'gross' => 0.0), 'Donation' => array('n' => 0, 'gross' => 0.0), 'Kit' => array('n' => 0, 'gross' => 0.0), 'Merch' => array('n' => 0, 'gross' => 0.0));
if ($rows) {
    while ($r = $rows->fetch_assoc()) {
        $ledger[] = $r;
        $amt = ($r['amount'] === null || $r['amount'] === '') ? null : (float) $r['amount'];
        $fee = (float) $r['fee'];
        $isref = (int) $r['refunded'] === 1;
        if ($amt === null) {
            $unrecorded++;
        } elseif ($isref) {
            $refunded_amt += $amt; $refunded_n++;
        } else {
            $gross += $amt; $fees += $fee;
            if (isset($by_type[$r['src']])) { $by_type[$r['src']]['n']++; $by_type[$r['src']]['gross'] += $amt; }
        }
    }
}
$net = $gross - $fees;

// ---- CSV export (must precede any HTML) ----------------------------------
if (isset($_GET['download'])) {
    $label = $all_time ? 'all-time' : (string) $year;
    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename=untrobotics-ar-ledger-' . $label . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Date (UTC)', 'Type', 'Description', 'Gross', 'Fee', 'Net', 'Status', 'Processor', 'Transaction ID'));
    foreach ($ledger as $r) {
        $amt = ($r['amount'] === null || $r['amount'] === '') ? null : (float) $r['amount'];
        $fee = (float) $r['fee'];
        fputcsv($out, array(
            $r['ts'],
            $r['src'],
            $r['descr'],
            $amt === null ? '' : number_format($amt, 2, '.', ''),
            $amt === null ? '' : number_format($fee, 2, '.', ''),
            $amt === null ? '' : number_format($amt - $fee, 2, '.', ''),
            ((int) $r['refunded'] === 1) ? 'refunded' : ($amt === null ? 'unrecorded' : 'paid'),
            ledger_processor($r['txid']),
            $r['txid'],
        ));
    }
    fclose($out);
    die();
}

head('Finances', 'Finances');
require_once(BASE . '/admin/_styles.php');
$period_label = $all_time ? 'All time' : ('Tax year ' . $year);
$cur_year = (int) $untrobotics->get_current_year();
$money = function ($n) { return '$' . number_format($n, 2); };
?>
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="admin-wrap">
                <a class="admin-back" href="/admin">&larr; Admin</a>
                <div class="admin-head">
                    <h1>AR Ledger</h1>
                    <p class="lead">Every payment across dues, donations, kit preorders and merch in one ledger, with gross / processor fees / net for the selected tax year. Export the CSV for your records at tax time.</p>
                </div>

                <div class="admin-help">
                    <strong>How to read this</strong>
                    <ul>
                        <li><strong>Gross</strong> is money collected; <strong>Fees</strong> are payment processing fees; <strong>Net</strong> is what actually landed (Gross minus Fees). Refunded transactions are excluded from Gross and Net and totalled separately.</li>
                        <li><strong>Fees:</strong> a fee is only recorded when the processor reports it at payment time. PayPal does. Stripe reports its fee later and it isn't captured yet, so Stripe rows show a $0 fee and Net equals Gross. Gross is accurate; treat Net as approximate for Stripe rows until fee capture is added.</li>
                        <li>Periods are calendar years in <strong>UTC</strong>. The authoritative books are still Stripe + PayPal; this mirrors them for convenience.</li>
                        <?php if ($unrecorded > 0): ?>
                        <li><strong><?php echo $unrecorded; ?> older merch order<?php echo $unrecorded === 1 ? '' : 's'; ?></strong> in this period predate local amount capture, so their dollar figures aren't in the totals. Look them up in Stripe/PayPal by transaction id (shown as <span class="pill pill-neutral">Unrecorded</span>). New merch is captured automatically.</li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="admin-stats">
                    <?php
                    echo admin_stat($money($net), 'Net revenue', 'green');
                    echo admin_stat($money($gross), 'Gross collected', 'grey');
                    echo admin_stat($money($fees), 'Processor fees', 'amber');
                    echo admin_stat($money($refunded_amt) . ($refunded_n ? ' (' . $refunded_n . ')' : ''), 'Refunded', 'red');
                    ?>
                </div>

                <div class="admin-card">
                    <div class="hd">
                        <span><?php echo htmlspecialchars($period_label); ?>
                            <span class="sub">&nbsp;&middot;&nbsp;
                                <?php if (!$all_time): ?>
                                    <a href="?year=<?php echo $year - 1; ?>">&larr; <?php echo $year - 1; ?></a>
                                    <?php if ($year < $cur_year): ?>&nbsp;|&nbsp;<a href="?year=<?php echo $year + 1; ?>"><?php echo $year + 1; ?> &rarr;</a><?php endif; ?>
                                    &nbsp;|&nbsp;<a href="?year=all">all time</a>
                                <?php else: ?>
                                    <a href="?year=<?php echo $cur_year; ?>">this year</a>
                                <?php endif; ?>
                            </span>
                        </span>
                        <a class="btn-pill go" href="?year=<?php echo $all_time ? 'all' : $year; ?>&download">Download CSV</a>
                    </div>
                    <div class="bd" style="padding-bottom:6px;">
                        <div class="admin-stats" style="grid-template-columns:repeat(auto-fit,minmax(130px,1fr));margin-bottom:6px;">
                            <?php foreach ($by_type as $t => $agg) { echo admin_stat($money($agg['gross']), $t . ' (' . $agg['n'] . ')', 'grey'); } ?>
                        </div>
                    </div>
                    <div class="admin-table-wrap">
                        <table class="admin-table">
                            <thead><tr><th>Date</th><th>Type</th><th>Description</th><th>Gross</th><th>Fee</th><th>Net</th><th>Status</th><th>Processor</th><th>Transaction</th></tr></thead>
                            <tbody>
                            <?php if ($ledger): foreach ($ledger as $r):
                                $amt = ($r['amount'] === null || $r['amount'] === '') ? null : (float) $r['amount'];
                                $fee = (float) $r['fee'];
                                $isref = (int) $r['refunded'] === 1;
                            ?>
                                <tr<?php echo $isref ? ' class="is-dim"' : ''; ?>>
                                    <td><?php echo admin_ts($r['ts'], 'M j, Y'); ?></td>
                                    <td><?php echo htmlspecialchars($r['src']); ?></td>
                                    <td><?php echo htmlspecialchars($r['descr']) ?: '<span class="muted">-</span>'; ?></td>
                                    <td class="num"><?php echo $amt === null ? '<span class="muted">-</span>' : $money($amt); ?></td>
                                    <td class="num"><?php echo $amt === null ? '<span class="muted">-</span>' : $money($fee); ?></td>
                                    <td class="num"><?php echo $amt === null ? '<span class="muted">-</span>' : $money($amt - $fee); ?></td>
                                    <td><?php echo $isref ? admin_pill('refunded') : ($amt === null ? admin_pill('unrecorded', 'Unrecorded') : admin_pill('paid')); ?></td>
                                    <td><?php echo htmlspecialchars(ledger_processor($r['txid'])); ?></td>
                                    <td class="num"><span class="muted" style="font-size:11px;"><?php echo htmlspecialchars($r['txid']); ?></span></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="9" class="admin-empty">No transactions in <?php echo htmlspecialchars($period_label); ?>.</td></tr>
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
admin_ts_script();
footer();
