<?php
// this file is the PayPal webhook endpoint

/**
 * Puts a message in the PayPal webhook log file
 * @param $message mixed Message to put in the log file. Should be interpretable as a string
 */
function payment_log($message): void {
    file_put_contents('../logs/paypal-webhook.log', '[' . date('c', time()) . '] ' . $message . PHP_EOL, FILE_APPEND);
}

require_once('../../api/discord/bots/admin.php');   // used to send messages to the officer channel. Specifically, for donation notifications
require_once('../../api/paypal/webhook.php');   // used to verify the webhook event with PayPal's API
require_once('../../template/functions/paypal.php'); // used to email a receipt to the payer
require_once('../../template/top.php'); // used for access to $db
require_once('../../template/constants.php'); // used to send email info in discord instead of actually emailing

if ($_SERVER['REQUEST_METHOD'] === 'POST') { // webhook events are sent via POST

    // get event
    $event = new PaypalWebhookEvent(file_get_contents('php://input'));
    // verify the event. Only uses the API if CRC calc fails
    try {
        try {
            $verified = $event->verify_internal();
            // force verify external if internal verification failed
            if(!$verified){
                $crc_fail = true;
                throw new PayPalCustomApiException();
            }
        } catch (PayPalCustomApiException $e) {
            $verified = $event->verify_external();

            // if the internal CRC check returned unverified but PayPal's API returned verified, send a message with full webhook event and headers to web log channel
            if(isset($crc_fail) && $crc_fail && $verified){
                require_once('../../template/config.php');
                AdminBot::send_message("Webhook event verification failed CRC check but passed PayPal's API. Check certs.", ENVIRONMENT == Environment::DEVELOPMENT? DISCORD_DEV_WEB_LOGS_CHANNEL_ID: DISCORD_WEB_LOGS_CHANNEL_ID, [['type'=>'json','bin'=>json_encode($event->headers)], ['type'=>'json', 'bin'=>$event->raw]]);
            }
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

    // Ignore the event if it's already been handled
/*    if($event->already_handled()){
        http_response_code(200);
        error_log("Ignoring duplicate webhook event transmission. Webhook event ID: {$event->payload['id']}");
    }*/

    // perform actions based on what event occurred
    switch ($event->payload['event_type']) {
        // capture payment... this event is sent when order is approved by the payer
        case 'CHECKOUT.ORDER.APPROVED':
        {
            $order_id = $event->payload['resource']['id'];

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
            $paypal = new PayPalCustomApi();
            try {
                $order = json_decode($paypal->get_order_info($order_id), true);
            } catch (PayPalCustomApiException $e) {
                http_response_code(500);
                error_log("(Webhook) Failed to fetch order details for ID {$order_id} while processing captured payment: {$e->getMessage()}");
                die();
            }
            $items = $order['purchase_units'][0]['items'];
            // go over each item and handle it
            foreach($items as $item) {
                $item_type = 'donation';
                if(isset($item['upc'])){ // upc is used for uid which should only be there for dues
                    $item_type = 'dues';
                }
                if(isset($item['sku'])){ // sku is used for
                    $custom = json_decode($item['custom'], true);
                    if(isset($custom['src']))
                    {
                        $item_type = $custom['src'];    // should be printful
                    }
                }
                switch ($item['item_type']) {
                    case 'dues':
                    {
                        require_once('handlers/dues.php');
                        $dues = true;
//                        AdminBot::send_message('Found a dues payment!', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        \DUES\handle_payment_notification($order_info, $custom, intval($item['upc']['code']), $order_id);
                        break;
                    }
                    case 'printful_product':
                    {
                        require_once('handlers/printful.php');
//                        AdminBot::send_message('Found a printful order!', DISCORD_DEV_WEB_LOGS_CHANNEL_ID);
                        $printful_orders[] = \PRINTFUL\handle_payment_notification($order_info, $current_item_index, $item, $order_id, isset($dues), $event);
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

            //email payer the receipt
            $email = email_receipt($order_info, $printful_orders, isset($dues), ENVIRONMENT === Environment::PRODUCTION);
            if (is_array($email)) {
                AdminBot::send_message('Receipt email for dev PayPal.', DISCORD_DEV_WEB_LOGS_CHANNEL_ID, [['bin' => json_encode($email), 'type' => 'json']]);
            } else if ($email) {
                payment_log("[{$order_id}] Successfully sent e-mail receipt (" . var_export($email, true) . ")");
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