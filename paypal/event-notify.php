<?php
// this file is the PayPal webhook endpoint
require_once('../api/discord/bots/admin.php');
require_once('../api/paypal/webhook.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // webhook events are sent via POST
    $event = new PaypalWebhookEvent(file_get_contents('php://input'));
    try {
        // try to verify using PayPal's API before relying on the certificate.... Consider swapping the order
        try {
            $verified = $event->verify_external();
        } catch (PayPalCustomApiException $e) {
            $verified = $event->verify_internal();
        }
    } catch (Exception $e) {
        // catch all exceptions, we assume the issue is with PayPal
        http_response_code(400);
        throw $e;
    }
    if (!$verified) {
        http_response_code(400);
        error_log("Received a webhook event that couldn't be verified. Webhook event ID: {$event->payload['id']}");
    }
//    http_response_code(200);    // this is implied
    switch ($event->payload['event_type']) {
        case 'CHECKOUT.ORDER.APPROVED':
        {
            // capture payment
            require_once('../api/paypal/paypal.php');
            $paypal = new PayPalCustomApi();
            $result = json_decode($paypal->capture_payment($event->payload['resource']['id']), true);
            if ($result['status'] !== 'COMPLETED') {
                error_log("Issue capturing payment for order ID {$event->payload['resource']['id']}. Order status is {$result['status']}");
            }

            break;
        }
        case 'PAYMENT.CAPTURE.COMPLETED':
        {
            // check if printful or dues
            break;
        }
        default:
        {
            error_log("Unknown or unhandled PayPal webhook event: {$event->payload['event_type']}");
        }
    }
} else{
    http_response_code(403);
}