<?php
// this file is the PayPal webhook endpoint
require_once('../api/discord/bots/admin.php');
require_once('../api/paypal/webhook.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // webhook events are sent via POST
    $event = new PaypalWebhookEvent(file_get_contents('php://input'));
    AdminBot::send_message('Received message on webhook', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
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
        error_log("PayPal Event Verification Failed: " . $e->getMessage());
    }
    if (!$verified) {
        http_response_code(400);
        error_log("Received a webhook event that couldn't be verified. Webhook event ID: {$event->payload['id']}. Payload: " . print_r($event->payload, true));
        die();
    }
    AdminBot::send_message('Message verified', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
//    http_response_code(200);    // this is implied
    switch ($event->payload['event_type']) {

        // event is sent when payer approves an order
        case 'CHECKOUT.ORDER.APPROVED':
        {
            AdminBot::send_message('Trying to capture payment', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
            // capture payment
            require_once('../api/paypal/paypal.php');
            $paypal = new PayPalCustomApi();
            $result = json_decode($paypal->capture_payment($event->payload['resource']['id']), true);
            if ($result['status'] !== 'COMPLETED') {
                error_log("Issue capturing payment for order ID {$event->payload['resource']['id']}. Order status is {$result['status']}");
                http_response_code(500);
            } else {
                http_response_code(200);
                AdminBot::send_message('Payment captured', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
            }
            break;
        }
        // event is sent when we capture payment
        case 'PAYMENT.CAPTURE.COMPLETED':
        {
            // get items
            error_log(var_export($event->payload,true));
            $order_id = $event->payload['resource']['supplementary_data']['related_ids']['order_id'];
            require_once('../template/top.php');
            global $db;
            $rows = $db->query("SELECT 
                                    paypal_items.id AS item_id, 
                                    item_type, 
                                    item_name, 
                                    external_id 
                                FROM 
                                    paypal_orders 
                                LEFT JOIN 
                                    paypal_order_item 
                                ON 
                                    paypal_orders.id = paypal_order_item.order_id 
                                LEFT JOIN
                                    paypal_items 
                                ON 
                                    paypal_order_item.item_id = paypal_items.id
                                WHERE
                                    paypal_orders.paypal_order_id = '{$order_id}'");
            if (!$rows) {
                error_log("Paypal Order not found for order ID {$order_id}");
                die();
            }
            $item = $rows->fetch_assoc();
            while ($item !== null) {
                switch ($item['item_type']) {
                    case 'dues':
                    {
//                        AdminBot::send_message('Adding Good Standing role', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        break;
                    }
                    case 'printful_product':
                    {
//                        AdminBot::send_message('Creating Printful order', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        break;
                    }
                    case 'donation':{
//                        AdminBot::send_message('Received donation. Doing nothing', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
//                        http_response_code(200);
//                        die();
                    }
                }

                $item = $rows->fetch_assoc();
            }

            break;
        }
        default:
        {
            error_log("Unknown or unhandled PayPal webhook event: {$event->payload['event_type']}");
        }
    }
} else {
    http_response_code(403);
}