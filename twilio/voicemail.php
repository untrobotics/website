<?php
require_once('../template/config.php');

if ($_GET['code'] !== API_SECRET) {
	http_response_code(401);
	die();
}

require_once("../api/discord/bots/admin.php");

$body = $_POST['RecordingUrl'] . '.mp3';
if (empty($body)) {
	$body = "No message body...";
}
$from = $_POST['From'];
$location = $_POST['FromCity'] . ', ' . $_POST['FromState'];
$sid = $_POST['CallSid'];
$duration = $_POST['RecordingDuration'];


AdminBot::send_message("Received VOICEMAIL message (#{$sid}):\nFrom: {$from} ({$location})\nLength: {$duration} s\n\nClick to listen: {$body}");