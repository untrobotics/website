<?php
/*
 * PaymentIntent creator for the inline Express Checkout Element (Apple Pay /
 * Google Pay / Link on the page itself, no redirect). The amount is always
 * recomputed server-side, exactly like create-checkout-session.php. Returns
 * {clientSecret}; the wallet payment is confirmed client-side and fulfilled by
 * the payment_intent.succeeded branch of webhook.php.
 */

require('../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
require_once(BASE . '/api/stripe/vendor/autoload.php');

header('Content-Type: application/json');

function fail($http, $message) {
    header($http);
    echo json_encode(array('error' => $message));
    die();
}

if (empty(STRIPE_SECRET_KEY)) {
    AdminBot::send_message("(Stripe) STRIPE_SECRET_KEY is not configured.");
    fail('HTTP/1.1 500 Internal Server Error', 'Payments are not configured.');
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$source = isset($_REQUEST['source']) ? $_REQUEST['source'] : '';

try {
    if ($source === 'donation') {
        $amount = round((float) (isset($_REQUEST['amount']) ? $_REQUEST['amount'] : 0), 2);
        if ($amount < 1 || $amount > 10000) {
            fail('HTTP/1.1 400 Bad Request', 'Please enter a donation amount between $1 and $10,000.');
        }
        $custom = array('source' => 'DONATION');
        $item_name = 'UNT Robotics Donation';

        $intent = \Stripe\PaymentIntent::create(array(
            'amount' => intval(round($amount * 100)),
            'currency' => 'usd',
            'automatic_payment_methods' => array('enabled' => true),
            'description' => $item_name,
            'metadata' => array(
                'source' => 'DONATION',
                'custom' => serialize($custom),
                'options' => json_encode(array()),
                'quantity' => '1',
                'item_name' => $item_name,
            ),
        ));

        echo json_encode(array('clientSecret' => $intent->client_secret));
    } else {
        fail('HTTP/1.1 400 Bad Request', 'Unknown payment source.');
    }
} catch (Exception $ex) {
    AdminBot::send_message("(Stripe) Failed to create payment intent ({$source}): " . $ex->getMessage());
    fail('HTTP/1.1 500 Internal Server Error', 'Unable to start payment. Please try again.');
}
