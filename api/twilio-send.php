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
	$params = array(
		'Body' => $message,
		'To'   => $to,
		'StatusCallback' => 'https://www.untrobotics.com/twilio/sms-status.php?code=' . API_SECRET,
	);
	// A2P 10DLC: send through the Messaging Service that carries the approved
	// campaign so carriers accept the message. Sending via the raw From number
	// (even a registered one) routes outside the campaign and returns 30034.
	// Fall back to From only if no Messaging Service is configured.
	if (defined('TWILIO_MESSAGING_SERVICE_SID') && TWILIO_MESSAGING_SERVICE_SID) {
		$params['MessagingServiceSid'] = TWILIO_MESSAGING_SERVICE_SID;
	} else {
		$params['From'] = PHONE_NUMBER;
	}
	$body = $media . http_build_query($params);

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
