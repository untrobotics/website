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

function post_message($message, $attachments = array(), $special_endpoint = false, $channel_id = false) {
	
	$headers = array();
	
	$post_data = array();
	$post_data['text'] = $message;
	$post_data['attachments'] = array();
	foreach ($attachments as $attachment) {
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
		$post_data['bot_id'] = GROUPME_BOT_ID;
		$headers[] = 'Content-Type: application/x-www-form-urlencoded';
	}
	
	$post = json_encode($post_data);	
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
	    error_log('ERROR (curl) when posting as BOT: ' . curl_error($ch));
	}
	
	curl_close ($ch);
}

function prepare_image($attachment) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://image.groupme.com/pictures');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($attachment['url']));
	curl_setopt($ch, CURLOPT_POST, 1);

	$headers = array();
	$headers[] = 'X-Access-Token: ' . GROUPME_ACCESS_TOKEN;
	$headers[] = 'Content-Type: ' . $attachment['type'];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when added an image to GroupMe: ' . curl_error($ch));
	}
	curl_close ($ch);
	
	$data = json_decode($result);
	return $data->payload->url;
}

function mention_everyone($message, $channel_id = GROUPME_CHANNEL_ID, $special_endpoint = false) {
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
	
	error_log(var_export($result, true));
	$data = json_decode($result);
	$members = $data->response->members;
	
	$attachment = array();
	$user_ids = array();
	$loci = array();
	
	$string = '';
	foreach ($members as $member) {
		$mention = '@' . $member->nickname . ' ';
		
		$user_ids[] = $member->user_id;
		//$loci[] = array(strlen($string), strlen($string) + strlen($mention) - 1);
		$loci[] = array(0, 9);
		$string .= $mention;
	}
	
	$attachment[0] = array();
	$attachment[0]['type'] = 'mentions';
	$attachment[0]['user_ids'] = $user_ids;
	$attachment[0]['loci'] = $loci;
	
	if ($special_endpoint) {
		// call endpoint
		post_message($message, $attachment, $special_endpoint, $channel_id);
	} else {
		post_message($message, $attachment, $channel_id);
	}
}
