<?php
require_once('../template/top.php');

if ($_GET['code'] != API_SECRET) {
	http_response_code(401);
	die();
}

require_once('groupme-funcs.php');
require_once('twilio-send.php');
require_once('twilio-sid.php');

$data = file_get_contents("php://input");
$data = json_decode($data);
if (!$data) {
	$data = $_POST;
}

$message = $data->text;
$sender_type = $data->sender_type;
$o = $attachments = $data->attachments;
$sender_id = $data->sender_id;

function parse_attachments($attachments) {
	$a = array();

	if (count($attachments) == 0) {
		return false;
	} else {
		foreach ($attachments as $attachment) {
			if (isset($attachment->url)) {
				$a[] = $attachment->url;
			}
		}
	}
	return $a;
}

$attachments = parse_attachments($attachments);

if ($sender_type == 'user') {
	if (preg_match('@^SMS#([a-z0-9]{34}) (.+)$@is', $message, $m)) {
		$sid = $m[1];
		$to = sid_to_phone_number($sid);
		$body = $m[2];
		$send_status = send_sms_message($body, $to, $attachments);
		post_message('Sent message to ' . $to . ', status: ' . $send_status);
	} else if (preg_match('@^SMS#(\+1[0-9]{10}) (.+)$@is', $message, $m)) {
		$to = $m[1];
		$body = $m[2];
		$send_status = send_sms_message($body, $to, $attachments);
		post_message('Sent message to ' . $to . ', status: ' . $send_status);
	} else if (preg_match('/^\@everyone (.+)$/is', $message, $m)) {
		$has_mentions = false;
		foreach ($o as $attachment) {
			if ($attachment['type'] == 'mentions') {
				$has_mentions = true;
			}
		}
		
		if (!$has_mentions) { // make sure the bot doesn't trigger itself
			$body = $m[0];
			$q = $db->query("SELECT uid, endpoint FROM `officers_groupme_access_tokens`");
			
			$registered_uids = array();
			
			while ($r = $q->fetch_array(MYSQLI_NUM)) {
				$registered_uids[$r[0]] = $r[1];
			}
			
			if (array_key_exists($sender_id, $registered_uids)) {
				mention_everyone($body, GROUPME_CHANNEL_ID, $registered_uids[$sender_id]);
			} else {
				mention_everyone($body, GROUPME_CHANNEL_ID);
			}
		}
	}
}