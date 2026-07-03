<?php
// Incoming SMS webhook (Twilio Messaging). Posts the message to the Discord
// admin channel as an embed; officers reply to that message in Discord and the
// interactive bot texts the sender back (see the bot's messageCreate handler +
// api/internal/send-sms.php).
require('../template/config.php');

if ($_GET['code'] !== API_SECRET) {
    http_response_code(401);
    die();
}
// Verify Twilio's signature when a token is configured (same as the call flow).
require('twilio-signature.php');
if (!validate_twilio_signature()) {
    http_response_code(403);
    die();
}

require_once(BASE . '/api/discord/bots/admin.php');

$body = isset($_POST['Body']) ? trim($_POST['Body']) : '';
if ($body === '') {
    $body = '_(no text)_';
}
$from = isset($_POST['From']) ? $_POST['From'] : '';
$location = trim((isset($_POST['FromCity']) ? $_POST['FromCity'] : '') . ', ' . (isset($_POST['FromState']) ? $_POST['FromState'] : ''), ', ');
$sid = isset($_POST['SmsSid']) ? $_POST['SmsSid'] : (isset($_POST['MessageSid']) ? $_POST['MessageSid'] : '');

// MMS media (if any) is forwarded as Discord attachments.
$attachments = array();
$media_count = @intval(isset($_POST['NumMedia']) ? $_POST['NumMedia'] : 0);
for ($i = 0; $i < $media_count; $i++) {
    $attachments[$i] = array(
        'type' => isset($_POST['MediaContentType' . $i]) ? $_POST['MediaContentType' . $i] : '',
        'url'  => isset($_POST['MediaUrl' . $i]) ? $_POST['MediaUrl' . $i] : '',
    );
}

// Discord expects `embeds` (an array) — the old `embed` (singular) was silently
// dropped, producing an empty-message 400. The SID + From are embedded so the
// bot can route a Discord reply back to the right number.
$embed = new stdClass();
$embed->title = 'Received SMS Message';
$embed->description = "**SID:** {$sid}\n**FROM:** {$from}" . ($location ? " _({$location})_" : '') . "\n\n{$body}";
$embed->footer = new stdClass();
$embed->footer->text = 'Reply to this message in Discord to text them back.';

$data = new stdClass();
$data->embeds = array($embed);

AdminBot::send_message($data, DISCORD_ADMIN_CHANNEL_ID, $attachments);
