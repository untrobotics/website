<?php
require('../../template/config.php');
if ($_GET['code'] !== API_SECRET) {
        http_response_code(401);
        die();
}
?><?xml version="1.0" encoding="UTF-8"?>
<Response>
	<Gather input="dtmf" timeout="5" numDigits="1" action="/twilio/find-first/call-connect.php?code=<?php echo API_SECRET; ?>">
		<Say voice="woman" language="en-GB">Hello, this is U N T Rubotics call handler, to be connected through to the incoming call please press 1.</Say>
	</Gather>
</Response>
