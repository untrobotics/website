<?php
require('../template/config.php');
if ($_GET['code'] !== API_SECRET) {
	http_response_code(401);
	die();
}
// Keep phone numbers out of normal config file!!
// This is just in case some other part of the system gets compromised, it stops all of our phone numbers being in the global scope of all pages on the website.
require('phone-numbers-config.php');
?><?xml version="1.0" encoding="UTF-8"?>
<Response>
	<?php
	switch (intval($_POST['Digits'])) {
		case 1: // Nick
			?><Dial><?php echo $phone_numbers['Nick']; ?></Dial><?php
			break;
		case 2: // Juan
			?><Dial><?php echo $phone_numbers['Juan']; ?></Dial><?php
			break;
		case 3: // Kyle
			?><Dial><?php echo $phone_numbers['Kyle']; ?></Dial><?php
			break;
		case 4: // Chase
			?><Dial><?php echo $phone_numbers['Chase']; ?></Dial><?php
			break;
		case 5: // Tim
			?><Dial><?php echo $phone_numbers['Tim']; ?></Dial><?php
			break;
		case 6: // David
			?><Dial><?php echo $phone_numbers['David']; ?></Dial><?php
			break;
		case 7: // Eric
			?><Dial><?php echo $phone_numbers['Eric']; ?></Dial><?php
			break;
		case 8: // Sebastian King
			?><Dial><?php echo $phone_numbers['Seb']; ?></Dial><?php
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
			// find first available?
			break;
		default:
			?>
			<Say>I did not understand the input, sorry. Goodbye!</Say>
			<?php
	}
?>
</Response>