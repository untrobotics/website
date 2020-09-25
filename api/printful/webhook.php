<?php
require('../../template/top.php');
require(BASE . '/api/printful/printful.php');
require(BASE . '/api/discord/bots/admin.php');

function webhook_log($message) {
	file_put_contents(BASE . '/admin/logging/printful-webhook.log', $message . PHP_EOL, FILE_APPEND);
}

$data = json_decode(file_get_contents('php://input'));

webhook_log(var_export($data, true));

$type = $data->type;
$created = $data->created;
$retries = $data->retries;
$store = $data->store;
//$data;

class PrintfulWebhookType {
	const PACKAGE_SHIPPED = 'package_shipped';
}

switch ($type) {
	case PrintfulWebhookType::PACKAGE_SHIPPED:
		// check if reshipment, i think that's the only verification required
		webhook_log("Received shipment event");
		$printful_shipped_event = new PrintfulShippedEvent($data->data);
		webhook_log("Order ID: " . $printful_shipped_event->get_order()->get_id());
		webhook_log("Shipment ID: " . $printful_shipped_event->get_shipment()->get_id());
		webhook_log("Tracking number: " . $printful_shipped_event->get_shipment()->get_tracking_number());
		webhook_log("Tracking URL: " . $printful_shipped_event->get_shipment()->get_tracking_url());
		
		AdminBot::send_message("(PRW) Notice: THIS IS A TEMPORARY NOTIFICATION. Order  #[{$printful_shipped_event->get_order()->get_id()}] has shipped.");
		break;
}