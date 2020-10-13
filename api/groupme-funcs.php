<?php
require_once("../template/config.php");

function endpoint_request($endpoint, $data, $headers) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $endpoint);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array($data, $headers)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
	    error_log('ERROR (curl) when posting as ENDPOINT: ' . curl_error($ch));
	}
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	curl_close ($ch);

	return $httpcode;
}

function post_message($message, $channel_id = false, $attachments = array(), $special_endpoint = false) {
	$headers = array();

	$post_data = array();
	$post_data['text'] = $message;
	$post_data['attachments'] = array();

	foreach ($attachments as $k => $attachment) {
		if (preg_match('/^image/i', $attachment['type'], $m)) {
			$post_data['attachments'][] = array('type' => preg_replace('@^image(/.+)$@', 'image', $attachment['type']), 'url' => prepare_image($attachment));
		} else if (preg_match('/mentions/i', $attachment['type'], $m)) {
			$post_data['attachments'][] = array('type' => 'mentions', 'user_ids' => $attachment['user_ids'], 'loci' => $attachment['loci']);
		}
	}

	if ($special_endpoint) {
		$post_data = array('message' => $post_data);
		$headers[] = 'Content-Type: application/json';

		return endpoint_request($special_endpoint, $post_data, $headers);
	} else {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.groupme.com/v3/bots/post');
		
		if ($channel_id === false) {
			$post_data['bot_id'] = CHANNEL_TO_BOT[key(CHANNEL_TO_BOT)]; // reset canno be used on a const.
		} else {
			$post_data['bot_id'] = CHANNEL_TO_BOT[$channel_id];
		}
		
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
	}

	$post = json_encode($post_data);

	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	// debug
	//curl_setopt($ch, CURLOPT_VERBOSE, 1);
	//curl_setopt($ch, CURLOPT_HEADER, 1);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when posting as BOT: ' . curl_error($ch));
	}

	curl_close($ch);
}

function get_image($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when downloading an image file for GroupMe prepare function: ' . curl_error($ch));
	}
	curl_close($ch);
	
	return $result;
}

function prepare_image($attachment) {
	$ch = curl_init();
	
	$image = get_image($attachment['url']);

	curl_setopt($ch, CURLOPT_URL, 'https://image.groupme.com/pictures');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $image);
	curl_setopt($ch, CURLOPT_POST, 1);

	$headers = array();
	$headers[] = 'X-Access-Token: ' . GROUPME_ACCESS_TOKEN;
	$headers[] = 'Content-Type: ' . $attachment['type'];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when adding an image to GroupMe: ' . curl_error($ch));
	}
	curl_close($ch);

	$data = json_decode($result);
	return $data->payload->url;
}

function get_all_members($channel_id) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://api.groupme.com/v3/groups/' . $channel_id . '?token=' . GROUPME_ACCESS_TOKEN);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

	$headers = array();
	$headers[] = 'Content-Type: application/json';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when getting channel #' . $channel_id . ' from GroupMe: ' . curl_error($ch));
	}
	curl_close ($ch);

	$data = json_decode($result);
	$members = $data->response->members;

	return $members;
}

function mention_everyone($message, $channel_id, $special_endpoint = false) {
	$members = get_all_members($channel_id);

	$original_message = $message;

	$attachment = array();
	$user_ids = array();
	$loci = array();

	//$string = '';
	foreach ($members as $member) {
		$mention = '@' . $member->nickname . ' ';

		$user_ids[] = $member->user_id;
		//$loci[] = array(strlen($string), strlen($string) + strlen($mention) - 1);
		$loci[] = array(0, 9);
		//$string .= $mention;
	}

	if (count($user_ids) > GROUPME_MAX_MENTIONS) {
		$append_increment_to_message = true;
		$increments = ceil(count($user_ids)/GROUPME_MAX_MENTIONS);
	} else {
		$append_increment_to_message = false;
	}

	$iteration = 1;

	while (count($user_ids) > 0) {
		if ($append_increment_to_message) {
			$message = $original_message . " (" . $iteration++ . "/" . $increments . ")";
		}

		$attachment[0] = array();
		$attachment[0]['type'] = 'mentions';

		$count = 0;
		foreach ($user_ids as $k => $user_id) {
			$count++;

			$attachment[0]['user_ids'][] = $user_ids[$k];
			$attachment[0]['loci'][] = $loci[$k];

			unset($user_ids[$k]);
			unset($loci[$k]);

			if ($count % 25 === 0) {
				break;
			}
		}

		if ($special_endpoint) {
			// call endpoint
			post_message($message, $channel_id, $attachment, $special_endpoint);
		} else {
			post_message($message, $channel_id, $attachment);
		}
	}
}

function mention_officers($message, $channel_id) {
	$officers = get_all_members(GROUPME_OFFICER_CHANNEL_ID);

	$attachment = array();
	$user_ids = array();
	$loci = array();

	//$string = '';
	foreach ($officers as $officer) {
		$mention = '@' . $officer->nickname;

		$user_ids[] = $officer->user_id;
		//$loci[] = array(strlen($string), strlen($string) + strlen($mention) - 1);
		$loci[] = array(0, 18);
		//$string .= $mention;
	}

	$attachment[0] = array();
	$attachment[0]['type'] = 'mentions';
	$attachment[0]['user_ids'] = $user_ids;
	$attachment[0]['loci'] = $loci;

	post_message($message, $channel_id, $attachment);
}

function mention_person($message, $channel_id, $person) {
	$members = get_all_members($channel_id); // this needs to be replaced with a single user get function

	$attachment = array();
	$user_ids = array();
	$loci = array();

	foreach ($members as $member) {
		if ($member->user_id === $person) {
			$mention = '@' . $member->nickname;

			$user_ids[] = $member->user_id;
			//$loci[] = array(strlen($string), strlen($string) + strlen($mention) - 1);
			$loci[] = array(0, strlen($mention));

			$message = $mention . ' ' . $message;
		}
	}

	$attachment[0] = array();
	$attachment[0]['type'] = 'mentions';
	$attachment[0]['user_ids'] = $user_ids;
	$attachment[0]['loci'] = $loci;

	post_message($message, $channel_id, $attachment);
}
