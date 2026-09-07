<?php
/**
 * Reconciliation backfill for AR-ledger amounts/fees (URW-240, follow-up to URW-232).
 *
 * Stripe does NOT post a charge's balance_transaction (which carries the real
 * processing fee) synchronously, so the webhook records fee=0 at payment time.
 * This script fills in the true amount/fee after the fact by retrieving each
 * transaction from the processor:
 *   - printful_order_tx.amount/fee where amount IS NULL   (merch)
 *   - kit_preorders / donations .fee where fee=0          (Stripe fee)
 * Guarded so it only fills empty rows (never double-counts).
 *
 * Stripe txids (cs_/pi_/ch_) resolve via the Stripe API. NOTE: the 26 historical
 * PayPal merch txids are CLASSIC (IPN) transaction ids — the v2 REST API returns
 * PERMISSION_DENIED and classic NVP GetTransactionDetails returns no amount fields,
 * so they cannot be backfilled programmatically (see URW-240 / ask Seb).
 *
 * Usage (in the web pod):  DRY_RUN=1 php scripts/backfill-payment-fees.php   (report only)
 *                          DRY_RUN=0 php scripts/backfill-payment-fees.php   (apply)
 * Intended to also run on a schedule to reconcile going-forward Stripe fees.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); die("cli only\n"); }
require(__DIR__ . '/../template/config.php');
require(__DIR__ . '/../api/stripe/vendor/autoload.php');
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
$DRY = getenv('DRY_RUN') !== '0';
$db = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);

function stripe_amount_fee($txid) {
    try {
        if (strpos($txid, 'cs_') === 0) {
            $s = \Stripe\Checkout\Session::retrieve(array('id' => $txid, 'expand' => array('payment_intent.latest_charge.balance_transaction')));
            $gross = $s->amount_total / 100; $pi = $s->payment_intent; $ch = is_object($pi) ? $pi->latest_charge : null;
        } else {
            $pi = \Stripe\PaymentIntent::retrieve(array('id' => $txid, 'expand' => array('latest_charge.balance_transaction')));
            $gross = $pi->amount / 100; $ch = $pi->latest_charge;
        }
        $bt = ($ch && is_object($ch)) ? $ch->balance_transaction : null;
        $fee = ($bt && is_object($bt)) ? $bt->fee / 100 : null;
        return array(number_format($gross, 2, '.', ''), $fee !== null ? number_format($fee, 2, '.', '') : null, 'ok');
    } catch (Exception $e) { return array(null, null, substr($e->getMessage(), 0, 60)); }
}
function is_stripe($t) { return strpos($t, 'cs_') === 0 || strpos($t, 'pi_') === 0 || strpos($t, 'ch_') === 0; }

echo 'MODE: ' . ($DRY ? "DRY RUN\n" : "APPLY\n");
// Merch amounts (Stripe only; PayPal classic can't be resolved via API)
$rows = $db->query("SELECT id,txid FROM printful_order_tx WHERE amount IS NULL ORDER BY id");
$ok = 0; $skip = 0;
while ($r = $rows->fetch_assoc()) {
    if (!is_stripe($r['txid'])) { $skip++; continue; }
    list($amt, $fee, $st) = stripe_amount_fee($r['txid']);
    if ($amt === null) { echo "  merch #{$r['id']} FAIL: $st\n"; continue; }
    echo "  merch #{$r['id']} amount=$amt fee=" . ($fee ?? '?') . "\n"; $ok++;
    if (!$DRY) { $f = $fee === null ? 'NULL' : '"' . $db->real_escape_string($fee) . '"'; $db->query('UPDATE printful_order_tx SET amount="' . $db->real_escape_string($amt) . '", fee=' . $f . ' WHERE id=' . (int) $r['id'] . ' AND amount IS NULL'); }
}
echo "  merch: filled=$ok, non-stripe-skipped=$skip\n";
// Stripe fees for kits/donations
foreach (array('kit_preorders', 'donations') as $t) {
    $rows = $db->query("SELECT id,txid FROM $t WHERE (fee IS NULL OR fee=0) AND (txid LIKE 'cs_%' OR txid LIKE 'pi_%' OR txid LIKE 'ch_%')");
    $c = 0;
    while ($r = $rows->fetch_assoc()) { list($amt, $fee, $st) = stripe_amount_fee($r['txid']); if ($fee === null) continue; $c++; if (!$DRY) $db->query('UPDATE ' . $t . ' SET fee="' . $db->real_escape_string($fee) . '" WHERE id=' . (int) $r['id'] . ' AND (fee IS NULL OR fee=0)'); }
    echo "  $t: $c fee(s)" . ($DRY ? ' would be' : '') . " updated\n";
}
