<?php
namespace DUES;
use AdminBot;
use Semester;
use \stdClass;
require_once(__DIR__ . "/printful.php");

/**
 *
 * @param array $order_info PayPal order info. JSON-decoded associative array. After function call, adds a field called "term_string"
 * @param mixed $custom Custom data for the order
 * @param int $uid The user ID the dues payment is associated with
 * @param string $order_id The order ID of the order
 * @return void
 * @throws \WebhookEventHandlerException Throws if there are issues with the database or data validation fails
 */
function handle_payment_notification(array &$order_info, $custom, int $uid, string $order_id) {

	global $db, $untrobotics;

    // get dues config price to verify payment amount
    $q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
    if (!$q || $q->num_rows !== 2) {
        AdminBot::send_message("Unable to determine the dues payment price (Webhook Event)");
        throw new \WebhookEventHandlerException("Unable to determine dues payment price (Webhook Event)");
    }

    $r = $q->fetch_all(MYSQLI_ASSOC);

    // convert the rows fetched into a single associative array
    $mapped_config = array();
    array_walk(
        $r,
        function(&$val, $_key) use (&$mapped_config)
        {
            $mapped_config[$val['key']] = $val['value'];
        }
    );

    $t_shirt_dues_purchase_price = $mapped_config['t_shirt_dues_purchase_price'];
    $single_semester_dues_price = $mapped_config['semester_price'];

    $is_tshirt_included = !empty($custom['include_tshirt']) && $custom['include_tshirt'] != false;
    var_dump($custom, $is_tshirt_included);

    // array to store info on each term (semester) paid for
    $paid_for_terms = array();

    $dues_term = $custom['semester'];
    $dues_term_n = constant("Semester::{$dues_term}");
    $dues_year = $custom['year'];

    $paid_for_terms[0] = new \stdClass();
    $paid_for_terms[0]->dues_term = $dues_term;
    $paid_for_terms[0]->dues_term_n = $dues_term_n;
    $paid_for_terms[0]->dues_year = $dues_year;

    $term_string = ucfirst(strtolower($dues_term)) . ' ' . $dues_year;


    $expected_amount = 0;
    if ($is_tshirt_included) {
        $expected_amount += $t_shirt_dues_purchase_price;
    }
	$expected_amount += $single_semester_dues_price;
//	if (isset($payment_info->options[2]) && isset($payment_info->options[3])) {
    if(isset($custom['extra_semester'])){
	    // dues have been paid for the full year
        $expected_amount += $single_semester_dues_price;

        $extra_dues_term = $custom['extra_semester'];
        $extra_dues_term_n = constant("Semester::{$extra_dues_term}");
        $extra_dues_year = $custom['extra_year'];

        $paid_for_terms[1] = new stdClass();
        $paid_for_terms[1]->dues_term = $extra_dues_term;
        $paid_for_terms[1]->dues_term_n = $extra_dues_term_n;
        $paid_for_terms[1]->dues_year = $extra_dues_year;

        $term_string .= ' & ' . ucfirst(strtolower($extra_dues_term)) . ' ' . $extra_dues_year;
    }
    $order_info['term_string'] = $term_string;
    $payment_capture = $order_info['purchase_units']['payments']['captures'][0];
    $amount_paid = $payment_capture['seller_receivable_breakdown']['gross_amount']['value'];
	$fee = $payment_capture['seller_receivable_breakdown']['paypal_fee']['value'];

    // customers can search for transactions by the payment ID
    // customers can't use the order ID
    // we can't get the transaction ID
    $payment_id = $payment_capture['id'];

    // get user from db. Throw error if query fails or if uid isn't unique
	$q = $db->query('SELECT * FROM users WHERE id = "' . $db->real_escape_string($uid) . '"');
	if (!$q) {
		throw new \WebhookEventHandlerException("[{$order_id}]: Unable to retrieve matching user from database for dues payment (uid: {$uid}) (error: {$db->error})");
	}
	if ($q->num_rows !== 1) {
		throw new \WebhookEventHandlerException("[{$order_id}]: Query for matching user for dues payment returned non-singular result (uid: {$uid}) (count: {$q->num_rows})");
	}
	$r = $q->fetch_array(MYSQLI_ASSOC);

	if ($amount_paid > 0) {
	    if ($amount_paid != $expected_amount) {
            throw new \WebhookEventHandlerException("[{$order_id}]: Amount paid did not match the expected amount ({$amount_paid} vs {$expected_amount})");
        }

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
                throw new \WebhookEventHandlerException("[{$order_id}]: Failed to insert dues payment record into Good Standing database (uid: {$uid}) (count: {$db->error})");
            } else {
                payment_log("[{$order_id}] Successfully created entry in the dues payments table (q: " . intval($q) . ") for term ({$term->dues_term} {$term->dues_year})");
            }
        }
        $payer = $order_info['payer'];
        /*$payer['name']['given_name'] . ' ' . $payer['name']['surname']*/
        AdminBot::send_message("(IPN) Alert: Successfully received dues payment from {$r['name']} (#$uid), paid for by {$payer['name']['given_name']} {$payer['name']['surname']}. # Semesters: " . count($paid_for_terms));

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

            // process t-shirt order
            /*if ($is_tshirt_included) {
                AdminBot::send_message("Processing T-shirt order with this dues payment: @{$tshirt_size}");
                $printful_custom = array(
                    'variant'=>"@{$tshirt_size}",
                    'discounts'=>array('paid-with-dues')
                );

                $printfulapi = new PrintfulCustomAPI();
                $sync_variant = $printfulapi->get_variant($printful_custom['variant']);

                $payment_info->mc_gross = $t_shirt_dues_purchase_price;
                $payment_info->options = array();
                $payment_info->options[0] = array();
                $payment_info->options[1] = array();
                $payment_info->options[2] = array();
                $payment_info->options[0][1] = "Shirt";
                $payment_info->options[1][1] = $sync_variant->get_name();
                $payment_info->options[2][1] = "Standard";
                \PRINTFUL\handle_payment_notification($ipn, $payment_info, $printful_custom);
            }*/

	} else {
        // Might be removable. This may be sent by another PayPal webhook event
        AdminBot::send_message("(IPN) Alert: Received a negative or zero amount payment capture. This usually indicates some kind of reversal/refund. Order ID: [{$order_id}].");
	}
}