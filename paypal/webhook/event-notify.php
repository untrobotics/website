<?php
// this file is the PayPal webhook endpoint

/**
 * Puts a message in the PayPal webhook log file
 * @param $message mixed Message to put in the log file. Should be interpretable as a string
 */
function payment_log($message): void {
    file_put_contents('logs/paypal-webhook.log', '[' . date('c', time()) . '] ' . $message . PHP_EOL, FILE_APPEND);
}

require_once('../../api/discord/bots/admin.php');   // used to send messages to the officer channel. Specifically, for donation notifications
require_once('../../api/paypal/webhook.php');   // used to verify the webhook event with PayPal's API
require_once('../../template/functions/paypal.php'); // used to email a receipt to the payer
require_once('../../template/top.php'); // used for access to $db

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // webhook events are sent via POST

    // get event
    $event = new PaypalWebhookEvent(file_get_contents('php://input'));

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

    // perform actions based on what event occurred
    switch ($event->payload['event_type']) {
        // capture payment... this event is sent when order is approved by the payer
        case 'CHECKOUT.ORDER.APPROVED':
        {
            global $db;
            $order_id = $event->payload['resource']['id'];

            $status = get_order_status_internal($order_id);

            // db error
            if ($status === null) {
                // send an error status since PayPal will resend webhook events a couple of times if a non-success is returned
                http_response_code(500);
                error_log("Failed to fetch PayPal order {$order_id} status from the db: {$db->error}");
                die();
            }

            // order doesn't exist in the db
            if (!$status) {
                // don't send an error status to PayPal since this can't be fixed with a webhook event resend. This may need to alert a financial officer to fix it
                error_log("Could not find PayPal order {$order_id} in the db.");
                die();
            }

            // order status should be created before we change it to approved. If it's already been approved, we already tried capturing payment. If it's captured, then we received confirmation that it's been captured.
            if ($status !== 'created') {
                error_log("PayPal order {$order_id} internal status is not created. Not capturing payment. Status: {$status}");
                die();
            }

            // change order status to approved
            if (!$db->query("UPDATE paypal_orders SET status = 'approved' where paypal_order_id = '{$order_id}'")) {
                http_response_code(500);
                error_log("Failed to update PayPal order {$order_id} status to captured: {$db->error}");
                die();
            }

            // capture payment
            $paypal = new PayPalCustomApi();
            $result = json_decode($paypal->capture_payment($order_id), true);

            if ($result['status'] !== 'COMPLETED') {
                http_response_code(500);    // not sure if a 500 is correct since it may be an issue with the API
                error_log("Issue capturing payment for order ID {$order_id}. Order status is {$result['status']} instead of COMPLETED");
            } else {
                http_response_code(200);
            }
            break;
        }
        // do stuff since order is complete... this is sent after PayPal confirms we captured payment
        case 'PAYMENT.CAPTURE.COMPLETED':
        {
            // get items
            $order_id = $event->payload['resource']['supplementary_data']['related_ids']['order_id'];
            // this info is used in the handlers, but we need to fetch order details to get the payment ID so there's no reason to pass it
            /*$order_total = Currency::from_string($event->payload['resource']['supplementary_data']['seller_receivable_breakdown']['gross_amount']['value']);
            $paypal_fee =  Currency::from_string($event->payload['resource']['supplementary_data']['seller_receivable_breakdown']['paypal_fee']['value']);*/


            global $db;
            // fetch our commitments to fulfill for the order
            $rows = $db->query("SELECT 
                                    paypal_items.id AS item_id, 
                                    item_type, 
                                    item_name, 
                                    external_id,
                                    uid,
                                    custom_data, 
                                    status,
                                    item_category,
                                    variant_name
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


            // attempt to get info for this order
            try {
                $order_info = json_decode($event->get_order_info($order_id), true);
            } catch (PayPalCustomApiException $e) {
                error_log("Failed to fetch order information for ID {$order_id}: {$e->getMessage()}");
                die();
            }

            // attempt to update status of order in our database
            if (!$db->query("UPDATE paypal_orders SET status = 'captured' where paypal_order_id = '{$order_id}'")) {
                error_log("Failed to update PayPal order {$order_id} status to captured: {$db->error}");
            }

            // array of Printful orders info returned by the Printful payment handler
            $printful_orders = [];
            $item = $rows->fetch_assoc();

            // don't do anything if the order has already been marked as captured in the db
            if ($item['status'] === 'captured') {
                die();
            }
            $current_item_index = 0;
            // go over each item and handle it
            while ($item !== null) {
                switch ($item['item_type']) {
                    case 'dues':
                    {
                        require_once('handlers/dues.php');
                        $dues = true;
                        AdminBot::send_message('Found a dues payment!', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        \DUES\handle_payment_notification($order_info, json_decode($item['custom_data'], true), $item['uid'], $order_id);
                        break;
                    }
                    case 'printful_product':
                    {
                        require_once('handlers/printful.php');
                        //todo
                        AdminBot::send_message('Found a printful order!', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        $printful_orders[] = \PRINTFUL\handle_payment_notification($order_info,$current_item_index,$item,$order_id,isset($dues),$event);
                        break;
                    }
                    case 'donation':
                    {
                        AdminBot::send_message("Received donation from {$order_info['payer']['name']['given_name']} {$order_info['payer']['name']['surname']} ({$order_info['payer']['email_address']}).");
                    }
                }

                $item = $rows->fetch_assoc();
                $current_item_index++;
            }

            // email payer the receipt
//            $email_send_status = email_receipt($order_info, $printful_orders, isset($dues));
//            if ($email_send_status) {
//                /** @noinspection PhpConditionAlreadyCheckedInspection */
//                payment_log("[{$order_id}] Successfully sent e-mail receipt (" . var_export($email_send_status, true) . ")");
//            }
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