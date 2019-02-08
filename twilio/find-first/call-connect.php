<?php
require('../../template/config.php');
if ($_GET['code'] !== API_SECRET) {
        http_response_code(401);
        die();
}
?><?xml version="1.0" encoding="UTF-8"?>
<Response>
	<?php
	switch (intval($_POST['Digits'])) {
		case 1:
			?><Dial><Queue><?php echo TWILIO_FIND_FIRST_QUEUE; ?></Queue></Dial><?php
		break;
		default:
			?><Say>I did not understand the input, sorry. Goodbye!</Say><?php
	}
	?>
</Response>
