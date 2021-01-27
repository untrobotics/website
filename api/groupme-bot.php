<?php
require_once('../template/top.php');

if ($_GET['code'] !== API_SECRET) {
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
$channel_id = $data->group_id;

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

$DEBUG = false;
if ($DEBUG) {
	error_log(var_export($data, true));
}

if ($sender_type == 'user') {
	if (preg_match('@^SMS#([a-z0-9]{34}) (.+)$@is', $message, $m) && $channel_id === GROUPME_OFFICER_CHANNEL_ID) {
		$sid = $m[1];
		$to = sid_to_phone_number($sid);
		$body = $m[2];
		$send_status = send_sms_message($body, $to, $attachments);
		post_message('Sent message to ' . $to . ', status: ' . $send_status, $channel_id);
	} else if (preg_match('@^SMS#(\+1[0-9]{10}) (.+)$@is', $message, $m) && $channel_id === GROUPME_OFFICER_CHANNEL_ID) {
		$to = $m[1];
		$body = $m[2];
		$send_status = send_sms_message($body, $to, $attachments);
		post_message('Sent message to ' . $to . ', status: ' . $send_status, $channel_id);
	} else if (preg_match('/^\@officers (.+)$/is', $message, $m)) {
		$has_mentions = false;
		foreach ($o as $attachment) {
			if ($attachment['type'] == 'mentions') {
				$has_mentions = true;
			}
		}

		if (!$has_mentions) { // make sure the bot doesn't trigger itself
			$body = $m[0];
			$body = preg_replace("/@officers/", "@potatoes_in_chief", $body);
			mention_officers($body, $channel_id);
		}
	} else if (preg_match('/^\@everyone (.+)$/is', $message, $m)) {
		$has_mentions = false;
		foreach ($o as $attachment) {
			if ($attachment['type'] == 'mentions') {
				$has_mentions = true;
			}
		}

		if (!$has_mentions) { // make sure the bot doesn't trigger itself
			error_log("@everyone triggered");
			$officers = get_all_members(GROUPME_OFFICER_CHANNEL_ID);

			$user_is_officer = false;
			foreach ($officers as $member) {
				if ($member->user_id == $sender_id) {
					$user_is_officer = true;
				}
			}

			if ($user_is_officer) {
				$body = $m[0];
				$body = preg_replace("/@everyone/", "@potatoes", $body);
				$q = $db->query('SELECT endpoint FROM `officers_groupme_access_tokens` WHERE uid = "' . $db->real_escape_string($sender_id) . '" AND channel_id = "' . $db->real_escape_string($channel_id) . '"');

				if ($q->num_rows > 0) {
					$r = $q->fetch_array(MYSQLI_NUM);
					$endpoint = $r[0];
					mention_everyone($body, $channel_id, $endpoint);
				} else {
					mention_everyone($body, $channel_id);
				}
			} else {
				mention_person('Only officers may use the @everyone feature.', $channel_id, $sender_id);
			}
		}
	}
}
