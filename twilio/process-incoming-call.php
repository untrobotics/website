<?php
require('../template/config.php');
if ($_GET['code'] !== API_SECRET) {
	http_response_code(401);
	die();
}
// Verify Twilio's request signature when a token is configured, in addition to
// the ?code check above (falls back to code-only if no token is set yet).
require('twilio-signature.php');
if (!validate_twilio_signature()) {
	http_response_code(403);
	die();
}
// Keep phone numbers out of normal config file!!
// This is just in case some other part of the system gets compromised, it stops all of our phone numbers being in the global scope of all pages on the website.
require('phone-numbers-config.php');
ob_start();
?><?xml version="1.0" encoding="UTF-8"?>
<Response>
	<?php
	switch (intval($_POST['Digits'])) {
		case 1:
			?>
			<Gather input="dtmf" timeout="10" numDigits="1" action="/twilio/process-incoming-call.php?code=<?php echo API_SECRET; ?>">
				<Pause length="2"/>
				<Say voice="woman" language="en-GB">
					Press 2 for Nicholas Tindle, Co-President.
					3 for Laurence Boyd, Vice-President.
                    4 for Carter Moore, Secretary.
					5 for Sophia Casas, Corporate Relation.
				</Say>
			</Gather>
			<?php
			break;
		case 2:
			?><Dial><?php echo PHONE_NUMBERS['NickT']; ?></Dial><?php
			break;
		case 3:
			?><Dial><?php echo PHONE_NUMBERS['LaurenceB']; ?></Dial><?php
			break;
		case 4:
			?><Dial><?php echo PHONE_NUMBERS['CarterM']; ?></Dial><?php
			break;
		case 5:
			?><Dial><?php echo PHONE_NUMBERS['SophiaC']; ?></Dial><?php
			break;
		case 9: // Voicemail
			?>
			<Say voice="woman" language="en-GB">
					Please leave a message after the beep.
			</Say>
			<Record action="/twilio/voicemail.php?code=<?php echo API_SECRET; ?>" trim="trim-silence" transcribeCallback="/twilio/transcribe-voicemail.php?code=<?php echo API_SECRET; ?>" />
			<?php
			break;
		case 0:
			// find first available
			?><Enqueue waitUrl="/twilio/hold-music/hold-music.php"><?php echo TWILIO_FIND_FIRST_QUEUE; ?></Enqueue><?php
			// <Queue> is global, let's make a request in the background to find the first person
			
			break;
		default:
			?>
			<Say>I did not understand the input, sorry. Goodbye!</Say>
			<?php
	}
?>
</Response><?php
// this code basically sends the output to the user and then continues execution in secret below
header('Connection: close');
header('Content-Length: ' . ob_get_length());
ob_end_flush();
ob_flush();
flush();

// the curl request must be done asynchronously, because the script called here checks to make sure the Queue size is greater than one,
// however the call won't get added to the queue until this XML is returned, which without the async call waits for the curl to finish
if (intval($_POST['Digits']) === 0) {
	// CallSid is attacker-controlled ($_POST) and gets concatenated into the
	// internal URL below, so only proceed for a well-formed Twilio SID and
	// rawurlencode it to avoid SSRF / parameter injection.
	$call_sid = $_POST['CallSid'] ?? '';
	if (preg_match('/^[A-Z]{2}[0-9a-f]{32}$/', $call_sid)) {
		$ff_url = 'https://' . $_SERVER['SERVER_NAME'] . '/twilio/find-first/find-first-available.php?code=' . API_SECRET . '&SID=' . rawurlencode($call_sid);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $ff_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// This is a server-to-server GET, so Twilio wouldn't sign it. When an auth
		// token is configured, sign it ourselves (signature over just the URL, no
		// POST body) so find-first-available's signature check accepts it.
		if (defined('TWILIO_AUTH_TOKEN') && TWILIO_AUTH_TOKEN !== '') {
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'X-Twilio-Signature: ' . compute_twilio_signature($ff_url, array()),
			));
		}
		curl_exec($ch);
		curl_close($ch);
	}
}
?>