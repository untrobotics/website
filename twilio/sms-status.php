<?php
// Twilio SMS delivery status callback. Twilio POSTs the message's FINAL status
// (delivered / undelivered / failed) here — the initial send only returns
// 'queued', so without this a carrier rejection (e.g. A2P 10DLC error 30034) is
// invisible. We log every terminal status and alert the admin channel on failure.
require('../template/config.php');

if ($_GET['code'] !== API_SECRET) {
    http_response_code(401);
    die();
}
require('twilio-signature.php');
if (!validate_twilio_signature()) {
    http_response_code(403);
    die();
}

$status = isset($_POST['MessageStatus']) ? $_POST['MessageStatus'] : '';
$to = isset($_POST['To']) ? $_POST['To'] : '';
$sid = isset($_POST['MessageSid']) ? $_POST['MessageSid'] : '';
$err = isset($_POST['ErrorCode']) ? $_POST['ErrorCode'] : '';

// Goes to the Apache error log -> forwarded to the Discord web-logs channel.
error_log("[sms-status] sid={$sid} to={$to} status={$status} error={$err}");

// Surface terminal failures in the admin channel so they are not silently dropped.
if ($status === 'undelivered' || $status === 'failed') {
    require_once(BASE . '/api/discord/bots/admin.php');
    $hint = ((string) $err === '30034')
        ? ' (30034 = sending number is not A2P 10DLC registered)'
        : '';
    AdminBot::send_message("\u{26A0}\u{FE0F} Outbound SMS to {$to} {$status} — error {$err}{$hint} (sid {$sid})");
}

http_response_code(204);
