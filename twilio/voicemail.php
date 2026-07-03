<?php
// Voicemail recording webhook (the <Record> action in process-incoming-call.php).
// Posts the recording to the Discord admin channel. (The separate transcribe
// callback, transcribe-voicemail.php, posts the transcription to the same place.)
require_once('../template/config.php');

if ($_GET['code'] !== API_SECRET) {
    http_response_code(401);
    die();
}
require('twilio-signature.php');
if (!validate_twilio_signature()) {
    http_response_code(403);
    die();
}

require_once(BASE . '/api/discord/bots/admin.php');

$recording = (isset($_POST['RecordingUrl']) && $_POST['RecordingUrl'] !== '') ? $_POST['RecordingUrl'] . '.mp3' : '';
$from = isset($_POST['From']) ? $_POST['From'] : '';
$location = trim((isset($_POST['FromCity']) ? $_POST['FromCity'] : '') . ', ' . (isset($_POST['FromState']) ? $_POST['FromState'] : ''), ', ');
$sid = isset($_POST['CallSid']) ? $_POST['CallSid'] : '';
$duration = isset($_POST['RecordingDuration']) ? $_POST['RecordingDuration'] : '?';

$embed = new stdClass();
$embed->title = 'Received Voicemail';
$embed->description = "**Call:** {$sid}\n**FROM:** {$from}" . ($location ? " _({$location})_" : '')
    . "\n**Length:** {$duration}s"
    . ($recording ? "\n\n[▶ Listen to the recording]({$recording})" : '');

$data = new stdClass();
$data->embeds = array($embed);

AdminBot::send_message($data, DISCORD_ADMIN_CHANNEL_ID);
