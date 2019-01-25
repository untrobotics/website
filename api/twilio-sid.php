<?php
require_once("../template/config.php");

function sid_to_phone_number($sid) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages/' . $sid . '.json');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

	curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when getting message SID info: ' . curl_error($ch));
	}
	curl_close ($ch);

	$data = json_decode($result);
	return $data->from;
}
