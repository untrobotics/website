<?php
require_once("../template/config.php");

function send_sms_message($message, $to, $attachments) {

	$media = '';
	foreach ($attachments as $attachment) {
		$media .= 'MediaUrl=' . $attachment . '&';
	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, "{$media}Body={$message}&From=" . PHONE_NUMBER . "&To={$to}");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

	$headers = array();
	$headers[] = 'Content-Type: application/x-www-form-urlencoded';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when sending SMS: ' . curl_error($ch));
	}

	//error_log($result);

	curl_close ($ch);

	$data = json_decode($result);

	return $data->status;
}
