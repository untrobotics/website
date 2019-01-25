<?php

require('groupme-endpoint-config.php');

function curl_installed() {
	return function_exists('curl_version') ? true : false;
}

function send_message($post, $headers) {
	$post = json_encode($post);
	
	if (curl_installed()) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, API_URL);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		curl_exec($ch);
		if (curl_errno($ch)) {
			error_log('ERROR (curl) when posting message as GroupMe user: ' . curl_error($ch));
			http_response_code(500);
		}
		curl_close ($ch);
		return true;
	} else {
		$opts = array('http' =>
			array(
				'method'  => 'POST',
				'header'  => $headers,
				'content' => $post
			)
		);
		$context  = stream_context_create($opts);
		if (!file_get_contents(API_URL, false, $context)) {
			http_response_code(500);
		}
		return true;
	}
}
	

if ($_GET['code'] == SECRET_CODE) {
	$body = $_POST[0];
	$headers = $_POST[1];
	
	if (!empty($body)) {
		send_message($body, $headers);
	} else {
		http_response_code(400);
	}
} else {
	http_response_code(401);
}