<?php
require_once('../template/top.php');

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
$transcription = $_POST['TranscriptionText'];


AdminBot::send_message("Transcribed VOICEMAIL message (#{$sid}):\nFrom: {$from}\n\nTranscription: {$transcription}");