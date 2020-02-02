<?php
require('../template/config.php');
if ($_GET['code'] !== API_SECRET) {
	http_response_code(401);
	die();
}
require_once("../api/groupme-funcs.php");

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
		$attachments[$i]['type'] = $_POST['MediaContentType' . $i];
		$attachments[$i]['url'] = $_POST['MediaUrl' . $i];
	}
}

/*
ob_start();
var_dump($_POST);
$result = ob_get_clean();

error_log($result);
*/

post_message("Received SMS message (#{$sid}):\nFrom: {$from} ({$location})\n\n{$body}", false, $attachments);
