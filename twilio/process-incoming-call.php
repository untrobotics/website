<?php
require('../template/config.php');
if ($_GET['code'] !== API_SECRET) {
	http_response_code(401);
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
					Press 2 for Sebastian King, Co-President.
					3 for Lauren Caves, Co-President.
                    4 for Tyler Martinez, Vice-President.
					5 for Ashank Annam, Corporate Relation.
					6 for Lauren Caves, Secretary.
				</Say>
			</Gather>
			<?php
			break;
		case 2:
			?><Dial><?php echo PHONE_NUMBERS['SebK']; ?></Dial><?php
			break;
		case 3:
			?><Dial><?php echo PHONE_NUMBERS['LaurenC']; ?></Dial><?php
			break;
		case 4:
			?><Dial><?php echo PHONE_NUMBERS['TylerM']; ?></Dial><?php
			break;
		case 5:
			?><Dial><?php echo PHONE_NUMBERS['AshankA']; ?></Dial><?php
			break;
        case 6:
            ?><Dial><?php echo PHONE_NUMBERS['LaurenC']; ?></Dial><?php
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
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, 'https://' . $_SERVER['SERVER_NAME'] . '/twilio/find-first/find-first-available.php?code=' . API_SECRET . '&SID=' . $_POST['CallSid']);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_exec($ch);
	curl_close($ch);
}
?>