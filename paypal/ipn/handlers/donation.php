<?php
/**
 * Donation handler — records a general sponsorship/donation and thanks the donor.
 * There is no fulfillment (no dues row, no Printful order); the amount is whatever
 * the donor chose (validated at create time). Reached via the shared adapter
 * (process_normalized_payment) from both the Stripe webhook and PayPal capture.
 */
namespace DONATION;

use AdminBot;

function handle_payment_notification($ipn, $payment_info, $custom) {
    global $db;

    $amount = $payment_info->mc_gross;
    $txid   = $payment_info->txn_id;
    $fee    = $payment_info->mc_fee;
    $name   = trim($payment_info->first_name . ' ' . $payment_info->last_name);
    $email  = $payment_info->payer_email;

    if (!is_numeric($amount) || $amount <= 0) {
        $ipn->alert("Alert: Donation with a non-positive amount ({$amount}) [{$txid}] — likely a reversal/refund.");
        return;
    }

    // Idempotency: a retry re-running this handler must not record the donation
    // twice (donations.txid is UNIQUE, but skip cleanly rather than erroring).
    $dup = $db->query('SELECT id FROM donations WHERE txid = "' . $db->real_escape_string($txid) . '" LIMIT 1');
    if ($dup && $dup->num_rows > 0) {
        payment_log("[{$txid}] Donation already recorded for this tx; skipping.");
        return;
    }

    $q = $db->query('INSERT INTO donations (name, email, amount, fee, txid)
        VALUES (
            "' . $db->real_escape_string($name) . '",
            "' . $db->real_escape_string($email) . '",
            "' . $db->real_escape_string($amount) . '",
            "' . $db->real_escape_string($fee) . '",
            "' . $db->real_escape_string($txid) . '"
        )');
    if (!$q) {
        throw new \IPNHandlerException("[{$txid}]: Failed to record donation (error: {$db->error})");
    }
    payment_log("[{$txid}] Recorded donation of \${$amount} from " . ($name !== '' ? $name : 'anonymous'));

    $ipn->alert("Alert: Received a \${$amount} donation from " . ($name !== '' ? $name : 'a supporter') . ($email !== '' ? " ({$email})" : '') . ". \xF0\x9F\x8E\x89");

    if ($email !== '') {
        $sent = email(
            $email,
            'Thank you for your donation to UNT Robotics',
            '<p>Dear ' . htmlspecialchars($name !== '' ? $name : 'friend') . ',</p>'
            . '<p>Thank you so much for your generous donation to UNT Robotics! Your support directly funds our robots, competitions, and workshops.</p>'
            . brand_email_code_box('$' . number_format((float) $amount, 2))
            . '<p style="margin-top:18px;">This email is your receipt.<br><strong>Transaction ID:</strong> ' . htmlspecialchars($txid) . '</p>'
            . '<p>If you have any questions, reach us at <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>.</p>'
        );
        payment_log("[{$txid}] Donation receipt email sent (" . var_export($sent, true) . ")");
    }
}
