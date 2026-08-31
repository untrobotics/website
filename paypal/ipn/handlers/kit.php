<?php
/**
 * Kit preorder handler — records an Electronics Kit prepayment and emails a receipt.
 *
 * Unlike the donation handler, the buyer's name/email come from the preorder FORM
 * (carried through the payment in $custom), not from the payment provider — the
 * form collects first/last/email before checkout. Reached via the shared adapter
 * (process_normalized_payment) from both the Stripe webhook and PayPal capture.
 */
namespace KIT;

use AdminBot;

function handle_payment_notification($ipn, $payment_info, $custom) {
    global $db;

    $amount = $payment_info->mc_gross;
    $txid   = $payment_info->txn_id;
    $fee    = $payment_info->mc_fee;

    $first = isset($custom['first_name']) ? trim($custom['first_name']) : '';
    $last  = isset($custom['last_name'])  ? trim($custom['last_name'])  : '';
    $email = isset($custom['email'])      ? trim($custom['email'])      : '';
    $phone = isset($custom['phone'])      ? trim($custom['phone'])      : '';
    $name  = trim($first . ' ' . $last);

    if (!is_numeric($amount) || $amount <= 0) {
        $ipn->alert("Alert: Kit preorder with a non-positive amount ({$amount}) [{$txid}] — likely a reversal/refund.");
        return;
    }

    // Idempotency: a retry re-running this handler must not record twice
    // (kit_preorders.txid is UNIQUE, but skip cleanly rather than erroring).
    $dup = $db->query('SELECT id FROM kit_preorders WHERE txid = "' . $db->real_escape_string($txid) . '" LIMIT 1');
    if ($dup && $dup->num_rows > 0) {
        payment_log("[{$txid}] Kit preorder already recorded for this tx; skipping.");
        return;
    }

    $q = $db->query('INSERT INTO kit_preorders (first_name, last_name, phone, email, amount, fee, txid)
        VALUES (
            "' . $db->real_escape_string($first) . '",
            "' . $db->real_escape_string($last) . '",
            "' . $db->real_escape_string($phone) . '",
            "' . $db->real_escape_string($email) . '",
            "' . $db->real_escape_string($amount) . '",
            "' . $db->real_escape_string($fee) . '",
            "' . $db->real_escape_string($txid) . '"
        )');
    if (!$q) {
        throw new \IPNHandlerException("[{$txid}]: Failed to record kit preorder (error: {$db->error})");
    }
    payment_log("[{$txid}] Recorded Electronics Kit preorder from " . ($name !== '' ? $name : 'unknown') . " ({$email})");

    $contact = $phone;
    if ($email !== '') { $contact .= ($contact !== '' ? ' / ' : '') . $email; }
    $ipn->alert("Alert: Electronics Kit preorder from " . ($name !== '' ? $name : 'someone') . ($contact !== '' ? " ({$contact})" : '') . " — \${$amount}. \xF0\x9F\xA4\x96");

    if ($email !== '') {
        $sent = email(
            $email,
            'Your UNT Robotics Electronics Kit preorder',
            '<p>Hi ' . htmlspecialchars($first !== '' ? $first : 'there') . ',</p>'
            . '<p>Thanks for preordering an <strong>Electronics Kit</strong>! Your payment is confirmed and we have you down. We\'ll assemble your kit and email you again as soon as it\'s ready to pick up.</p>'
            . brand_email_code_box('$' . number_format((float) $amount, 2))
            . '<p style="margin-top:18px;">Kits are handed out at our general meetings — check the '
            . '<a href="https://www.untrobotics.com/events">event calendar</a> and our '
            . '<a href="https://www.untrobotics.com/join/discord">Discord</a> for times &mdash; general meetings are held in room <strong>B185</strong>.</p>'
            . '<p style="margin-top:14px;">This email is your receipt.<br><strong>Transaction ID:</strong> ' . htmlspecialchars($txid) . '</p>'
            . '<p>Questions? Reach us at <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>.</p>'
        );
        payment_log("[{$txid}] Kit preorder receipt email sent (" . var_export($sent, true) . ")");
    }
}
