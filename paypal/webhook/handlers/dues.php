<?php

namespace DUES;

use AdminBot;
use Semester;
use \stdClass;

require_once(__DIR__ . '/../../../api/paypal/webhook.php');

use WebhookEventHandlerException;

require_once(__DIR__ . "/printful.php");

/**
 * Handles a dues payment notification. Adds information to the dues_payments table and sends some messages to the officer Discord channel
 * @param array $order_info PayPal order info as a JSON-decoded associative array. Use {@see \PayPalCustomApi::get_order_info()} to easily get the order info. After function call, adds a field called "term_string" to the array.
 * @param mixed $custom Custom data for the order. Used fields are 'include_tshirt', 'semester', 'year', 'extra_year' and 'extra_semester'. If 'extra_semester' is not set, 'extra_year' is ignored
 * @param int $uid The user ID the dues payment is associated with
 * @param string $order_id The PayPal order ID of the order. This is not the ID of the order in the paypal_orders table; this is the ID that PayPal generates
 * @return void
 * @throws WebhookEventHandlerException Throws if there are issues with the database or data validation fails
 */
function handle_payment_notification(array &$order_info, $custom, int $uid, string $order_id) {

    global $db, $untrobotics;

    // get dues price from dues_config table to verify payment amount
    $q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
    if (!$q || $q->num_rows !== 2) {
        AdminBot::send_message("Unable to determine the dues payment price (Webhook Event)");
        throw new WebhookEventHandlerException("Unable to determine dues payment price (Webhook Event)");
    }

    $r = $q->fetch_all(MYSQLI_ASSOC);

    // convert the rows fetched into a single associative array
    $mapped_config = array();
    array_walk(
        $r,
        function (&$val, $_key) use (&$mapped_config) {
            $mapped_config[$val['key']] = $val['value'];
        }
    );

    // puts the expected prices in variables
    $t_shirt_dues_purchase_price = $mapped_config['t_shirt_dues_purchase_price'];
    $single_semester_dues_price = $mapped_config['semester_price'];

    // determine if a dues shirt was bought
    $is_tshirt_included = !empty($custom['include_tshirt']) && $custom['include_tshirt'] != false;
    var_dump($custom, $is_tshirt_included); // not sure what this does

    // array to store info on each term (semester) paid for
    $paid_for_terms = array();

    // dues_term_n refers to the semester number, based on Semester constants (see template/classes/untrobotics.php)
    $dues_term_n = $custom['semester'];
    $dues_term = Semester::get_name_from_value($dues_term_n);
    $dues_year = $custom['year'];   // year the dues payment is for

    // paid_for_terms is used to insert rows into the dues_payments table
    $paid_for_terms[0] = new \stdClass();
    $paid_for_terms[0]->dues_term = $dues_term;
    $paid_for_terms[0]->dues_term_n = $dues_term_n;
    $paid_for_terms[0]->dues_year = $dues_year;

    // term_string is used for the receipt to tell the payer which semester(s) they paid for
    $term_string = ucfirst(strtolower($dues_term)) . ' ' . $dues_year;

    // expected payment amount. This is gross - discounts i.e., excludes fees, taxes, and CoGS
    $expected_amount = 0;
    // add dues shirt price to expected payment amount
    if ($is_tshirt_included) {
        $expected_amount += $t_shirt_dues_purchase_price;
    }
    // add a single semester's dues price to the expected payment amount
    $expected_amount += $single_semester_dues_price;

    // extra_semester is only used if the user paid for Spring and Fall
    // add info for another dues payment
    if (isset($custom['extra_semester'])) {
        $expected_amount += $single_semester_dues_price;

        $extra_dues_year = $custom['extra_year'];
        $extra_dues_term_n = $custom['extra_semester'];
        $extra_dues_term = Semester::get_name_from_value($extra_dues_term_n);
        $paid_for_terms[1] = new stdClass();
        $paid_for_terms[1]->dues_term = $extra_dues_term;
        $paid_for_terms[1]->dues_term_n = $extra_dues_term_n;
        $paid_for_terms[1]->dues_year = $extra_dues_year;

        // append another semester to term_string
        $term_string .= ' & ' . ucfirst(strtolower($extra_dues_term)) . ' ' . $extra_dues_year;
    }
    // sets term_string in order_info so that it can be used for the receipt
    $order_info['term_string'] = $term_string;

    $payment_capture = $order_info['purchase_units'][0]['payments']['captures'][0];
    $amount_paid = $payment_capture['seller_receivable_breakdown']['gross_amount']['value'];
    $fee = $payment_capture['seller_receivable_breakdown']['paypal_fee']['value'];

    // get user from db. Throw error if query fails or if uid isn't unique
    $q = $db->query('SELECT * FROM users WHERE id = "' . $db->real_escape_string($uid) . '"');
    if (!$q) {
        throw new WebhookEventHandlerException("[{$order_id}]: Unable to retrieve matching user from database for dues payment (uid: {$uid}) (error: {$db->error})");
    }
    if ($q->num_rows !== 1) {
        throw new WebhookEventHandlerException("[{$order_id}]: Query for matching user for dues payment returned non-singular result (uid: {$uid}) (count: {$q->num_rows})");
    }
    $r = $q->fetch_array(MYSQLI_ASSOC);

    // ignore if the amount paid is nonzero
    if ($amount_paid > 0) {
        // throw an error if the user didn't pay the expected amount
        if ($amount_paid != $expected_amount) {
            throw new WebhookEventHandlerException("[{$order_id}]: Amount paid did not match the expected amount ({$amount_paid} vs {$expected_amount})");
        }

        // add each term to the dues_payments table
        foreach ($paid_for_terms as $term) {
            $q = $db->query('INSERT INTO dues_payments (name, email, euid, amount, fee, txid, dues_term, dues_year, uid)
            VALUES (
            "' . $db->real_escape_string($r['name']) . '",
            "' . $db->real_escape_string($r['email']) . '",
            "' . $db->real_escape_string($r['unteuid']) . '",
            "' . $db->real_escape_string($single_semester_dues_price) . '",
            "' . $db->real_escape_string($fee) . '",
            "' . $db->real_escape_string($order_id) . '",
            "' . $db->real_escape_string($term->dues_term_n) . '",
            "' . $db->real_escape_string($term->dues_year) . '",
            "' . $db->real_escape_string($uid) . '"
            )');

            if (!$q) {
                throw new WebhookEventHandlerException("[{$order_id}]: Failed to insert dues payment record into Good Standing database (uid: {$uid}) (count: {$db->error})");
            } else {
                payment_log("[{$order_id}] Successfully created entry in the dues payments table (q: " . intval($q) . ") for term ({$term->dues_term} {$term->dues_year})");
            }
        }

        // payer is only used to send dues notification message to the officer channel
        $payer = $order_info['payer'];
        /*$payer['name']['given_name'] . ' ' . $payer['name']['surname']*/
        AdminBot::send_message("(Webhook) Alert: Successfully received dues payment from {$r['name']} (#$uid), paid for by {$payer['name']['given_name']} {$payer['name']['surname']}. # Semesters: " . count($paid_for_terms));

        // sends a message to the officer channel with the total dues received for the academic year
        $sum = "UNKNOWN";
        if ($untrobotics->get_current_term() == Semester::AUTUMN) {
            // get dues payments from this semester
            $term = Semester::AUTUMN;
            $year = $untrobotics->get_current_year();
            $next_term = Semester::SPRING;
            $next_year = $untrobotics->get_next_year();

            $q = $db->query("SELECT SUM(amount) FROM dues_payments WHERE
                    (dues_term = " . $db->real_escape_string($term) . " AND
                    dues_year = " . $db->real_escape_string($year) . ") OR
                    (dues_term = " . $db->real_escape_string($next_term) . " AND
                    dues_year = " . $db->real_escape_string($next_year) . ")
                ");

            if ($q) {
                $sum = $q->fetch_row()[0];
            }
        } else {
            // get dues payments from this semester and last semester
            $term = Semester::SPRING;
            $year = $untrobotics->get_current_year();
            $last_term = Semester::AUTUMN;
            $last_year = $untrobotics->get_last_year();

            $q = $db->query("SELECT SUM(amount) FROM dues_payments WHERE
                    (dues_term = " . $db->real_escape_string($term) . " AND
                    dues_year = " . $db->real_escape_string($year) . ") OR
                    (dues_term = " . $db->real_escape_string($last_term) . " AND
                    dues_year = " . $db->real_escape_string($last_year) . ")
                    ");

            if ($q) {
                $sum = $q->fetch_row()[0];
            }
        }
        AdminBot::send_message("Amount received in dues so far this academic year: \${$sum}");
    } else {
        // todo: Might be removable. This may be sent by another PayPal webhook event. Payment.Capture.Refunded or Payment.Capture.Reversed
        AdminBot::send_message("(Webhook) Alert: Received a negative or zero amount payment capture. This usually indicates some kind of reversal/refund. Order ID: [{$order_id}].");
    }
}