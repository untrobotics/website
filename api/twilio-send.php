<?php
require_once("../template/config.php");

function send_sms_message($message, $to, $attachments) {

	// Build the x-www-form-urlencoded body with every value properly encoded.
	// $message, $to and each MediaUrl are caller/attacker-controlled, so raw
	// interpolation would allow injecting extra Twilio parameters. http_build_query
	// can't emit repeated MediaUrl= keys from an array, so encode those by hand.
	$media = '';
	foreach ($attachments as $attachment) {
		$media .= 'MediaUrl=' . urlencode($attachment) . '&';
	}

	// StatusCallback: Twilio POSTs the FINAL delivery status to this webhook so
	// carrier rejections (e.g. A2P 10DLC 30034) are logged/alerted instead of
	// being masked by the initial 'queued' response.
	$body = $media . http_build_query(array(
		'Body' => $message,
		'From' => PHONE_NUMBER,
		'To'   => $to,
		'StatusCallback' => 'https://www.untrobotics.com/twilio/sms-status.php?code=' . API_SECRET,
	));

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://api.twilio.com/2010-04-01/Accounts/" . TWILIO_ACCOUNT_SID . "/Messages.json");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
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
