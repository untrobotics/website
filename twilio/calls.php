<?php
// One may think, why do I have to hide this page? It only contains the same text you can hear if you phone our number.
// It is because it prints our API_SECRET in the URL, and being able to browse it would compromise that secret string.
require_once('../template/config.php');

if ($_GET['code'] !== API_SECRET) {
	http_response_code(401);
	die();
}
?><?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Gather input="dtmf" timeout="10" numDigits="1" action="/twilio/process-incoming-call.php?code=<?php echo API_SECRET; ?>">
		<Pause length="2"/>
        <Say voice="woman" language="en-GB">
			Thank you for calling U.N.T. Ru botics.
			Please press 1 for Nicholas Tindle, Organisation President.
			2 for Juan Ru eez, Vice President.
			3 for Michelle Vargas, Public Relations.
			4 for Alec Slonina, Organisation Secretary.
			5 for Tim Stern, Treasurer.
			6 for David Woodward, Event Coordinator.
			7 for Eric King, Inventory Manager.
			8 for Sebastian King, Webmaster.
			Press 9 to leave us a voicemail.
			Or, press 0 to be put through to the first available person.
		</Say>
    </Gather>
	<Say voice="woman" language="en-GB">We didn't receive any input. Goodbye!</Say>
</Response>
