<?php

$token = $_GET['token'];

function get_user_info($token) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://api.groupme.com/v3/users/me?token=' . $token);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('ERROR (curl) when getting user info by access token for GroupMe: ' . curl_error($ch));
	}
	curl_close ($ch);
	
	$data = json_decode($result);
	return $data->response;
}

echo '<pre>';

$user = get_user_info($token);

$id = intval($user->id);
var_dump($id);