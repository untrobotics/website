<?php
// this file is the PayPal webhook endpoint
require_once('../../api/discord/bots/admin.php');
require_once('../../api/paypal/webhook.php');
require_once('../../template/functions/paypal.php');
require_once('../../template/top.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // webhook events are sent via POST

    // get event
    $event = new PaypalWebhookEvent(file_get_contents('php://input'));

    AdminBot::send_message('Received message on webhook', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);

    // verify the event. Tries with PayPal's API before using the CRC
    try {
        try {
            $verified = $event->verify_external();
        } catch (PayPalCustomApiException $e) {
            $verified = $event->verify_internal();
        }
    } catch (Exception $e) {
        // assume the issue is with PayPal
        http_response_code(400);
        error_log("PayPal Event Verification Failed: " . $e->getMessage());
        die();
    }

    // send 400 if message isn't verified
    if (!$verified) {
        http_response_code(400);
        error_log("Received a webhook event that couldn't be verified. Webhook event ID: {$event->payload['id']}. Payload: " . print_r($event->payload, true));
        die();
    }
//    AdminBot::send_message('Message verified', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);

    // perform actions based on what event occurred
    switch ($event->payload['event_type']) {
        // capture payment... this event is sent when order is approved by the payer
        case 'CHECKOUT.ORDER.APPROVED':
        {
            global $db;
            $order_id = $event->payload['resource']['id'];

            $status = get_order_status_internal($order_id);

            // db error
            if($status === null){
                http_response_code(500);
                error_log("Failed to fetch PayPal order {$order_id} status from the db: {$db->error}");
                die();
            }
            // order doesn't exist in the db
            if(!$status){
                error_log("Could not find PayPal order {$order_id} in the db.");
                die();
            }
            // payment was already captured, or the user never approved payment (so we can't capture payment)
            if($status!=='approved'){
                die();
            }

            // change order status to approved
            if (!$db->query("UPDATE paypal_orders SET status = 'approved' where paypal_order_id = '{$order_id}'")) {
                error_log("Failed to update PayPal order {$order_id} status to captured: {$db->error}");
            }

            // capture payment
            AdminBot::send_message('Trying to capture payment', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);

            $paypal = new PayPalCustomApi();
            $result = json_decode($paypal->capture_payment($order_id), true);

            if ($result['status'] !== 'COMPLETED') {
                error_log("Issue capturing payment for order ID {$order_id}. Order status is {$result['status']} instead of COMPLETED");
                http_response_code(500);
            } else {
                http_response_code(200);
                AdminBot::send_message('Payment captured', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
            }
            break;
        }
        // do stuff since order is complete... this is sent after PayPal confirms we captured payment
        case 'PAYMENT.CAPTURE.COMPLETED':
        {
            // get items
            error_log(var_export($event->payload, true));
            $order_id = $event->payload['resource']['supplementary_data']['related_ids']['order_id'];
            // this info is used in the handlers, but we need to fetch order details to get the payment ID so there's no reason to pass it
            /*$order_total = Currency::from_string($event->payload['resource']['supplementary_data']['seller_receivable_breakdown']['gross_amount']['value']);
            $paypal_fee =  Currency::from_string($event->payload['resource']['supplementary_data']['seller_receivable_breakdown']['paypal_fee']['value']);*/
            global $db;
            $rows = $db->query("SELECT 
                                    paypal_items.id AS item_id, 
                                    item_type, 
                                    item_name, 
                                    external_id,
                                    uid,
                                    custom_data
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
            if (!$db->query("UPDATE paypal_orders SET status = 'captured' where paypal_order_id = '{$order_id}'")) {
                error_log("Failed to update PayPal order {$order_id} status to captured: {$db->error}");
            }
            try {
                $order_info = json_decode($event->get_order_info($order_id), true);
            } catch (PayPalCustomApiException $e) {
                error_log("Failed to fetch order information for ID {$order_id}: {$e->getMessage()}");
                die();
            }

            $printful_orders = [];
            // go over each item and handle it
            $item = $rows->fetch_assoc();
            while ($item !== null) {
                switch ($item['item_type']) {
                    case 'dues':
                    {
                        $dues = true;
                        \DUES\handle_payment_notification($order_info, json_decode($item['custom_data'],true),  $item['uid'], $order_id);
//                        AdminBot::send_message('Adding Good Standing role', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        break;
                    }
                    case 'printful_product':
                    {
                        //todo
                        $printful_orders[] = \PRINTFUL\handle_payment_notification($event, json_decode($item['custom_data'],true));
//                        AdminBot::send_message('Creating Printful order', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        break;
                    }
                    case 'donation':
                    {
//                        AdminBot::send_message('Received donation. Doing nothing', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
//                        http_response_code(200);
//                        die();
                    }
                }

                $item = $rows->fetch_assoc();
            }
            $email_send_status = email_receipt($order_info,$printful_orders, isset($dues));
            if ($email_send_status) {
                /** @noinspection PhpConditionAlreadyCheckedInspection */
                payment_log("[{$order_id}] Successfully sent e-mail receipt (" . var_export($email_send_status, true) . ")");
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