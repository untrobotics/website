<?php
require('../template/config.php');
if ($_GET['code'] !== API_SECRET) {
	http_response_code(401);
	die();
}
require_once(BASE . '/api/discord/bots/admin.php');

$body = $_POST['Body'];
if (empty($body)) {
	$body = "No message body...";
}
$from = $_POST['From'];
$location = $_POST['FromCity'] . ', ' . $_POST['FromState'];
$sid = $_POST['SmsSid'];

$attachments = array();
$media_count = @intval($_POST['NumMedia']);
if ($media_count > 0) {
	for($i = 0; $i < $media_count; $i++) {
		$attachments[$i] = array();
		$attachments[$i]['type'] = $_POST['MediaContentType' . $i];
		$attachments[$i]['url'] = $_POST['MediaUrl' . $i];
	}
}

/* debug
ob_start();
var_dump($_POST);
$result = ob_get_clean();

error_log($result);
*/

$data = new stdClass();
$data->embed = new stdClass();
$data->embed->title = "Received SMS Message";
$data->embed->description = "**SID:** #{$sid}\n**FROM:** {$from} _({$location})_\n\n{$body}";

AdminBot::send_message($data, DISCORD_ADMIN_CHANNEL_ID, $attachments);
