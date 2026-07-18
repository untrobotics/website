<?php
/**
 * Stripe webhook endpoint.
 *
 * Verifies the signature, handles checkout.session.completed, builds an
 * IPNResponse-shaped record and hands it to the shared completion adapter
 * (paypal/ipn/process_payment.php), which writes the exact same dues_payments /
 * Printful side effects as the PayPal IPN. Idempotency is enforced via the
 * handled_ipns table keyed on the Checkout Session id, so a replayed webhook
 * cannot double-insert.
 *
 * Configure the endpoint in the Stripe dashboard as:
 *   https://<host>/api/stripe/webhook.php   (event: checkout.session.completed)
 * and put the resulting "whsec_..." signing secret in STRIPE_WEBHOOK_SECRET.
 */

require('../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
require_once(BASE . '/api/stripe/vendor/autoload.php');
require_once(BASE . '/paypal/ipn/process_payment.php');

// Run with the same working directory as the PayPal IPN so the handlers'
// relative paths (logs/ipn.log, ../images/...) resolve identically.
chdir(BASE . '/paypal');

$payload = @file_get_contents('php://input');
$sig_header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';

if (empty(STRIPE_WEBHOOK_SECRET)) {
    http_response_code(500);
    AdminBot::send_message("(Stripe) STRIPE_WEBHOOK_SECRET is not configured; webhook rejected.");
    die();
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, STRIPE_WEBHOOK_SECRET);
} catch (\UnexpectedValueException $e) {
    http_response_code(400); // invalid payload
    die();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400); // invalid signature
    payment_log("[stripe] Webhook signature verification failed");
    die();
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Inline Express Checkout Element (Apple Pay / Google Pay / Link) fulfils via
// the PaymentIntent directly. A Checkout Session's own PI has no source/custom
// metadata (that lives on the session), so it's skipped here and handled by the
// checkout.session.completed branch — the two paths never double-fulfil.
if ($event->type === 'payment_intent.succeeded') {
    try {
        $pi_id = $event->data->object->id;
        $intent = \Stripe\PaymentIntent::retrieve(array(
            'id' => $pi_id,
            'expand' => array('latest_charge.balance_transaction'),
        ));

        $metadata = $intent->metadata ? $intent->metadata->toArray() : array();
        $source = isset($metadata['source']) ? $metadata['source'] : null;
        $custom_obj = isset($metadata['custom']) ? @unserialize($metadata['custom']) : false;
        if (!is_array($custom_obj) || $source === null) {
            http_response_code(200); // not an Express Checkout Element payment
            die();
        }
        $option_pairs = isset($metadata['options']) ? json_decode($metadata['options'], true) : array();
        if (!is_array($option_pairs)) {
            $option_pairs = array();
        }

        $is_sandbox = (strpos((string) STRIPE_SECRET_KEY, 'sk_test') === 0);
        $gateway = new PaymentGatewayContext($is_sandbox, 'Stripe');

        $amount_total = isset($intent->amount) ? $intent->amount : 0;
        $currency = isset($intent->currency) ? strtoupper($intent->currency) : 'USD';
        $charge = (isset($intent->latest_charge) && is_object($intent->latest_charge)) ? $intent->latest_charge : null;
        $fee_cents = 0;
        if ($charge && isset($charge->balance_transaction) && is_object($charge->balance_transaction)) {
            $fee_cents = (int) $charge->balance_transaction->fee;
        }
        $mc_gross = number_format($amount_total / 100, 2, '.', '');
        $mc_fee = number_format($fee_cents / 100, 2, '.', '');

        $billing = ($charge && isset($charge->billing_details)) ? $charge->billing_details : null;
        $email = ($billing && isset($billing->email)) ? $billing->email : '';
        $phone = ($billing && isset($billing->phone)) ? $billing->phone : '';
        $full_name = ($billing && isset($billing->name)) ? $billing->name : '';

        // Shipping is attached to the PI at confirm time for physical goods.
        $shipping = (isset($intent->shipping) && is_object($intent->shipping)) ? $intent->shipping : null;
        if ($shipping && !empty($shipping->name)) {
            $full_name = $shipping->name;
        }
        $name_parts = preg_split('/\s+/', trim($full_name), 2);
        $first_name = isset($name_parts[0]) ? $name_parts[0] : '';
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

        $fields = array(
            'mc_gross' => $mc_gross,
            'payment_gross' => $mc_gross,
            'mc_fee' => $mc_fee,
            'payment_fee' => $mc_fee,
            'mc_currency' => $currency,
            'txn_id' => $pi_id,
            'payment_date' => date('c', isset($intent->created) ? $intent->created : time()),
            'payment_status' => 'Completed',
            'payer_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'contact_phone' => $phone,
            'item_name' => isset($metadata['item_name']) ? $metadata['item_name'] : '',
            'quantity' => isset($metadata['quantity']) ? $metadata['quantity'] : '1',
            'custom' => isset($metadata['custom']) ? $metadata['custom'] : '',
        );

        if ($shipping && isset($shipping->address) && is_object($shipping->address)) {
            $addr = $shipping->address;
            $street = isset($addr->line1) ? $addr->line1 : '';
            if (!empty($addr->line2)) {
                $street .= "\n" . $addr->line2;
            }
            $fields['address_status'] = 'confirmed';
            $fields['address_name'] = $full_name;
            $fields['address_street'] = $street;
            $fields['address_city'] = isset($addr->city) ? $addr->city : '';
            $fields['address_state'] = isset($addr->state) ? $addr->state : '';
            $fields['address_zip'] = isset($addr->postal_code) ? $addr->postal_code : '';
            $fields['address_country_code'] = isset($addr->country) ? $addr->country : '';
            $fields['residence_country'] = isset($addr->country) ? $addr->country : '';
        }

        $payment_info = build_ipn_response($fields, $option_pairs);
        payment_log("[{$pi_id}] Stripe payment_intent.succeeded received (source: {$source}, gross: {$mc_gross} {$currency}, fee: {$mc_fee}, sandbox: " . ($is_sandbox ? 'true' : 'false') . ")");
        process_normalized_payment($gateway, $payment_info, $custom_obj, $pi_id);

        http_response_code(200);
        echo json_encode(array('received' => true));
    } catch (Exception $ex) {
        payment_log("[stripe] ERROR processing payment_intent webhook: " . $ex);
        AdminBot::send_message("(Stripe) Exception while processing payment_intent webhook: " . $ex->getMessage());
        // Return 5xx so Stripe retries — the handler released its claim, and the
        // handlers are idempotent by txid, so a retry fulfils exactly once rather
        // than leaving the buyer charged with no order.
        http_response_code(500);
        echo json_encode(array('received' => false, 'note' => 'retry'));
    }
    die();
}

// We only fulfill on completed checkout sessions.
if ($event->type !== 'checkout.session.completed') {
    http_response_code(200);
    die();
}

try {
    $session_id = $event->data->object->id;

    // Re-retrieve with everything we need expanded (amount, fee, address, email).
    $session = \Stripe\Checkout\Session::retrieve(array(
        'id' => $session_id,
        'expand' => array(
            'line_items',
            'payment_intent.latest_charge.balance_transaction',
        ),
    ));

    // Only fulfill paid sessions (ignore unpaid / async-pending).
    if (isset($session->payment_status) && $session->payment_status !== 'paid') {
        payment_log("[{$session_id}] Stripe session not paid (status: {$session->payment_status}); ignoring.");
        http_response_code(200);
        die();
    }

    $metadata = $session->metadata ? $session->metadata->toArray() : array();
    $source = isset($metadata['source']) ? $metadata['source'] : null;
    $custom_obj = isset($metadata['custom']) ? @unserialize($metadata['custom']) : false;
    $option_pairs = isset($metadata['options']) ? json_decode($metadata['options'], true) : array();
    if (!is_array($option_pairs)) {
        $option_pairs = array();
    }

    if (!is_array($custom_obj) || $source === null) {
        throw new IPNHandlerException("[{$session_id}]: Missing/invalid Stripe metadata (custom/source).");
    }

    // Test keys (sk_test) => sandbox: a real Printful order is never confirmed.
    $is_sandbox = (strpos((string) STRIPE_SECRET_KEY, 'sk_test') === 0);
    $gateway = new PaymentGatewayContext($is_sandbox, 'Stripe');

    // ---- Amounts -----------------------------------------------------------
    $amount_total = isset($session->amount_total) ? $session->amount_total : 0; // cents
    $currency = isset($session->currency) ? strtoupper($session->currency) : 'USD';

    $fee_cents = 0;
    if (isset($session->payment_intent) && is_object($session->payment_intent)
        && isset($session->payment_intent->latest_charge)
        && is_object($session->payment_intent->latest_charge)
        && isset($session->payment_intent->latest_charge->balance_transaction)
        && is_object($session->payment_intent->latest_charge->balance_transaction)) {
        $fee_cents = (int) $session->payment_intent->latest_charge->balance_transaction->fee;
    }

    $mc_gross = number_format($amount_total / 100, 2, '.', '');
    $mc_fee = number_format($fee_cents / 100, 2, '.', '');

    // ---- Buyer + address ---------------------------------------------------
    $details = isset($session->customer_details) ? $session->customer_details : null;
    $email = $details && isset($details->email) ? $details->email : '';
    $phone = $details && isset($details->phone) ? $details->phone : '';

    // Shipping (when collected). Newer API nests it under collected_information.
    $shipping = null;
    if (!empty($session->shipping_details)) {
        $shipping = $session->shipping_details;
    } elseif (isset($session->collected_information) && !empty($session->collected_information->shipping_details)) {
        $shipping = $session->collected_information->shipping_details;
    }

    // Name: prefer the shipping name, fall back to the billing name.
    $full_name = '';
    if ($shipping && isset($shipping->name) && $shipping->name) {
        $full_name = $shipping->name;
    } elseif ($details && isset($details->name)) {
        $full_name = $details->name;
    }
    $name_parts = preg_split('/\s+/', trim($full_name), 2);
    $first_name = isset($name_parts[0]) ? $name_parts[0] : '';
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

    $fields = array(
        'mc_gross' => $mc_gross,
        'payment_gross' => $mc_gross,
        'mc_fee' => $mc_fee,
        'payment_fee' => $mc_fee,
        'mc_currency' => $currency,
        'txn_id' => $session_id,
        'payment_date' => date('c', isset($session->created) ? $session->created : time()),
        'payment_status' => 'Completed',
        'payer_email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'contact_phone' => $phone,
        'item_name' => isset($metadata['item_name']) ? $metadata['item_name'] : '',
        'quantity' => isset($metadata['quantity']) ? $metadata['quantity'] : '1',
        'custom' => isset($metadata['custom']) ? $metadata['custom'] : '',
    );

    if ($shipping && isset($shipping->address) && is_object($shipping->address)) {
        $addr = $shipping->address;
        $street = isset($addr->line1) ? $addr->line1 : '';
        if (!empty($addr->line2)) {
            $street .= "\n" . $addr->line2;
        }
        $fields['address_status'] = 'confirmed';
        $fields['address_name'] = $full_name;
        $fields['address_street'] = $street;
        $fields['address_city'] = isset($addr->city) ? $addr->city : '';
        $fields['address_state'] = isset($addr->state) ? $addr->state : '';
        $fields['address_zip'] = isset($addr->postal_code) ? $addr->postal_code : '';
        $fields['address_country_code'] = isset($addr->country) ? $addr->country : '';
        $fields['residence_country'] = isset($addr->country) ? $addr->country : '';
    }

    $payment_info = build_ipn_response($fields, $option_pairs);

    payment_log("[{$session_id}] Stripe checkout.session.completed received (source: {$source}, gross: {$mc_gross} {$currency}, fee: {$mc_fee}, sandbox: " . ($is_sandbox ? 'true' : 'false') . ")");

    process_normalized_payment($gateway, $payment_info, $custom_obj, $session_id);

    http_response_code(200);
    echo json_encode(array('received' => true));
} catch (Exception $ex) {
    // Handler failed and released its claim; return 5xx so Stripe retries. The
    // handlers are idempotent by txid, so the retry fulfils exactly once instead
    // of leaving the buyer charged with no order.
    payment_log("[stripe] ERROR processing webhook: " . $ex);
    AdminBot::send_message("(Stripe) Exception while processing webhook: " . $ex->getMessage());
    http_response_code(500);
    echo json_encode(array('received' => false, 'note' => 'retry'));
}
