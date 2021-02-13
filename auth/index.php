<?php
$protocol = $_SERVER['SERVER_PROTOCOL'];
if (!preg_match("@HTTP/\d\.\d@i", $protocol)) {
	die(http_response_code(403));
}

header("{$protocol} 403 Piss Off.");
?>
