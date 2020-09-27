<?php
require('../../template/top.php');
require(BASE . '/api/printful/printful.php');
require(BASE . '/api/discord/bots/admin.php');

function webhook_log($message) {
	file_put_contents(BASE . '/admin/logging/printful-webhook.log', $message . PHP_EOL, FILE_APPEND);
}

$data = json_decode(file_get_contents('php://input'));

$type = $data->type;
$created = $data->created;
$retries = $data->retries;
$store = $data->store;
//$data;

class PrintfulWebhookType {
	const PACKAGE_SHIPPED = 'package_shipped';
	const PACKAGE_RETURNED = 'package_returned';
}

try {
	switch ($type) {
		case PrintfulWebhookType::PACKAGE_RETURNED:
			webhook_log(PHP_EOL . PHP_EOL . "Received returned event");
			$printful_returned_event = new PrintfulReturnedEvent($data->data);

			// log this request
			webhook_log("Order ID: " . $printful_returned_event->get_order()->get_id());
			webhook_log("Shipment ID: " . $printful_returned_event->get_shipment()->get_id());
			webhook_log("Carrier: " . $printful_returned_event->get_shipment()->get_carrier());
			webhook_log("Service: " . $printful_returned_event->get_shipment()->get_service());
			webhook_log("Tracking number: " . $printful_returned_event->get_shipment()->get_tracking_number());
			webhook_log("Tracking URL: " . $printful_returned_event->get_shipment()->get_tracking_url());
			webhook_log("Shipping Date: " . $printful_returned_event->get_shipment()->get_ship_date());
			webhook_log("Re-shipment: " . $printful_returned_event->get_shipment()->get_reshipment());
			
			$log_prefix = $printful_returned_event->get_shipment()->get_id() . '/' . $printful_returned_event->get_order()->get_id(); 
			
			$q = $db->query('UPDATE printful_order SET returned = 1 WHERE order_id = "' . $db->real_escape_string($printful_returned_event->get_order()->get_id()) . '"');	
			
			if (!$q) {
				webhook_log("[{$log_prefix}] Failed to set returned flag on printful order: {$db->error}.");
				throw new PrintfulWebhookException("Failed to set returned flag on printful order #{$printful_returned_event->get_order()->get_id()}.");
			}
			
			$affected_rows = $db->affected_rows;
			
			if ($affected_rows !== 1) {
				webhook_log("[{$log_prefix}] Failed to singularly set returned flag on printful order. (affected: {$affected_rows}).");
				throw new PrintfulWebhookException("Failed to singularly set returned flag on printful order. (affected: {$affected_rows}).");
			}
			
			
			webhook_log("[{$log_prefix}] Order has been returned and the database has been updated (reason: {$printful_returned_event->get_reason()}).");
			throw new PrintfulWebhookException("Failed to singularly set returned flag on printful order. (affected: {$affected_rows}).");
			
			break;
		case PrintfulWebhookType::PACKAGE_SHIPPED:
			webhook_log(PHP_EOL . PHP_EOL . "Received shipment event");
			$printful_shipped_event = new PrintfulShippedEvent($data->data);

			// log this request
			webhook_log("Order ID: " . $printful_shipped_event->get_order()->get_id());
			webhook_log("Shipment ID: " . $printful_shipped_event->get_shipment()->get_id());
			webhook_log("Carrier: " . $printful_shipped_event->get_shipment()->get_carrier());
			webhook_log("Service: " . $printful_shipped_event->get_shipment()->get_service());
			webhook_log("Tracking number: " . $printful_shipped_event->get_shipment()->get_tracking_number());
			webhook_log("Tracking URL: " . $printful_shipped_event->get_shipment()->get_tracking_url());
			webhook_log("Shipping Date: " . $printful_shipped_event->get_shipment()->get_ship_date());
			webhook_log("Re-shipment: " . $printful_shipped_event->get_shipment()->get_reshipment());
			
			$log_prefix = $printful_shipped_event->get_shipment()->get_id() . '/' . $printful_shipped_event->get_order()->get_id(); 
			
			webhook_log("[{$log_prefix}] Starting processing.");

			// check if reshipment, i think that's the only verification required
			$is_reshipment = $printful_shipped_event->get_shipment()->get_reshipment();
			
			// create an entry in the database for the shipment information
			$q = $db->query('
						INSERT INTO printful_shipment
						(order_id, shipment_id, carrier, service, tracking_number, tracking_url, ship_date, ship_time, reshipment)
						VALUES
						(
							"' . $db->real_escape_string($printful_shipped_event->get_order()->get_id()) . '",
							"' . $db->real_escape_string($printful_shipped_event->get_shipment()->get_id()) . '",
							"' . $db->real_escape_string($printful_shipped_event->get_shipment()->get_carrier()) . '",
							"' . $db->real_escape_string($printful_shipped_event->get_shipment()->get_service()) . '",
							"' . $db->real_escape_string($printful_shipped_event->get_shipment()->get_tracking_number()) . '",
							"' . $db->real_escape_string($printful_shipped_event->get_shipment()->get_tracking_url()) . '",
							"' . $db->real_escape_string($printful_shipped_event->get_shipment()->get_ship_date()) . '",
							"' . $db->real_escape_string($printful_shipped_event->get_shipment()->get_shipped_at()) . '",
							"' . $db->real_escape_string($is_reshipment) . '"						
						)
						');
			if (!$q) {
				webhook_log("[{$log_prefix}] Failed to commit shipment to printful_shipment table, returned error: {$db->error}.");
				throw new PrintfulWebhookException("Failed to commit shipment to printful shipment database table for order #{$printful_shipped_event->get_order()->get_id()}.");
			} else {
				webhook_log("[{$log_prefix}] Committed shipment to printful_shipment table.");
			}
			$printful_shipment_db_id = $db->insert_id;
			
			// retrieve the information from the original
			$q = $db->query('SELECT * FROM printful_order WHERE order_id = "' . $db->real_escape_string($printful_shipped_event->get_order()->get_id()) . '"');
			if (!$q) {
				throw new PrintfulWebhookException("Failed to retrieve printful order information from the database for order #{$printful_shipped_event->get_order()->get_id()}.");
			}
			if ($q->num_rows == 1) {
				webhook_log("[{$log_prefix}] Successfully retrieved order information.");
			} else {
				throw new PrintfulWebhookException("Retrieved non-single result from the printful order information query (count: {$q->num_rows}).");
			}
			$r = $q->fetch_array(MYSQLI_ASSOC);
			
			if ($is_reshipment === true) {				
				// apologise for the inconvenience
				webhook_log("[{$log_prefix}] This is a reshipment. We are apologising.");
				$message = "Your purchase of <strong>{$r['order_name']} - {$r['order_variant_name']}</strong> has been re-shipped. We apologise for any inconvenience. Please see your new tracking information below.";
			} else {
				webhook_log("[{$log_prefix}] We are sending the first/only shipment notification.");
				$message = "Your purchase of <strong>{$r['order_name']} - {$r['order_variant_name']}</strong> has shipped! Please see your tracking information below.";
			}
			
			$email_send_status = email(
				$r['email_address'],
				"Your order has shipped! " . $r['order_name'],

				"<div style=\"position: relative;max-width: 100vw;text-align:center;\">" .
				'<img src="cid:untrobotics-email-header">' .

				'	<div></div>' .

				'<div style="text-align: left; max-width: 500px; display: inline-block;">' .
				"	<p>Dear " . $r['first_name'] . ' ' . $r['last_name'] . ",</p>" .
				"	<p>{$message}</p>" .
				'</div>' .

				'	<div></div>' .

				"	<div style=\"display: inline-block;padding: 15px;border: 1px solid #bdbdbd;border-radius: 10px;text-align: left;\">" .
				"		<h5 style=\"font-size: 12pt;margin: 0;font-weight: 600;\">📦 Shipment Information</h5>" .
				"		<ul>" .
				"			<li><strong>Order #</strong> {$printful_shipped_event->get_order()->get_id()}</li>" .
				"			<li><strong>Shipment #</strong> {$printful_shipped_event->get_shipment()->get_id()}</li>" .
				"			<li><strong>Carrier</strong> {$printful_shipped_event->get_shipment()->get_carrier()}</li>" .
				"			<li><strong>Shipping Service</strong> {$printful_shipped_event->get_shipment()->get_service()}</li>" .
				"			<li><strong>Tracking Number</strong> <a href=\"{$printful_shipped_event->get_shipment()->get_tracking_url()}\">{$printful_shipped_event->get_shipment()->get_tracking_number()}</a></li>" .
				"			<li><strong>Shipment Date</strong> {$printful_shipped_event->get_shipment()->get_ship_date()}</li>" .
				"		</ul>" .
				"	</div>" .
				"	<p></p>" .
				"	<p>If you need any assistance with your order, please reach out to <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a>.</p>" .
				"</div>",

				false,
				null,
				[
					[
						'content' => base64_encode(file_get_contents(BASE . '/images/unt-robotics-email-header.jpg')),
						'type' => 'image/jpeg',
						'filename' => 'unt-robotics-email-header.jpg',
						'disposition' => 'inline',
						'content_id' => 'untrobotics-email-header'
					]
				]
			);
			
			if ($email_send_status) {
				payment_log("Successfully sent e-mail with shipment notification (" . var_export($email_send_status, true) . ")");
			} else {
				webhook_log("[{$log_prefix}] Failed to sent e-mail with tracking information (status: " . ($email_send_status ? 'true' : 'false') . ").");
				AdminBot::send_message("(PRW) Alert: Failed to send e-mail with shipment information for order #[{$printful_shipped_event->get_shipment()->get_id()}/{$printful_shipped_event->get_order()->get_id()}].");
			}
			
			$q = $db->query('UPDATE printful_shipment SET confirmed = 1 WHERE id = "' . $db->real_escape_string($printful_shipment_db_id) . '"');
			if (!$q) {
				AdminBot::send_message("(PRW) Alert: Printful shipment table failed to commit confirmation update for [{$printful_shipped_event->get_shipment()->get_id()}/{$printful_shipped_event->get_order()->get_id()}]. (super secret id: {$printful_shipment_db_id})");
			} else {
				webhook_log("[{$log_prefix}] Successfully finished processing this shipment notification.");
				AdminBot::send_message("(PRW) Notice: THIS IS A TEMPORARY NOTIFICATION. Order #[{$printful_shipped_event->get_shipment()->get_id()}/{$printful_shipped_event->get_order()->get_id()}] has shipped.");
			}
			break;
		default:
			webhook_log(PHP_EOL . PHP_EOL . "Received a notification which does not have a corresponding handler: " . $type);
			webhook_log(var_export($data, true));
			AdminBot::send_message("(PRW) Error: Received a notification which does not have a corresponding handler: " . $type);
	}
} catch (Exception $ex) {
	webhook_log("[{$log_prefix}] Caught a processing exception (event: {$type}) (retries: {$retries}): {$ex}");
	if (isset($printful_shipped_event)) {
		AdminBot::send_message("(PRW) Exception: Failed to process [{$printful_shipped_event->get_shipment()->get_id()}/{$printful_shipped_event->get_order()->get_id()}] (event: {$type}) (retries: {$retries}): {$ex}");
	} else if (isset($printful_returned_event)) {
		AdminBot::send_message("(PRW) Exception: Failed to process [{$printful_returned_event->get_shipment()->get_id()}/{$printful_returned_event->get_order()->get_id()}] (event: {$type}) (retries: {$retries}): {$ex}");
	}
}

class PrintfulWebhookException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
	}
}