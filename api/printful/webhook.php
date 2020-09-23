<?php
require('../../template/top.php');

function webhook_log($message) {
	file_put_contents(BASE . '/admin/logging/printful-webhook.log', $message . PHP_EOL, FILE_APPEND);
}

$data = json_decode(file_get_contents('php://input'));

webhook_log(var_export($data, true));

$type = $data->type;
$created = $data->created;
$retries = $data->retries;
$store = $data->store;

class PrintfulWebhookType {
	const PACKAGE_SHIPPED = 'package_shipped';
}

switch ($type) {
	case PrintfulWebhookType::PACKAGE_SHIPPED:
		
		// do something
		break;
}