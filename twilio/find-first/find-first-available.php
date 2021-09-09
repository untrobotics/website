<?php
/*
ob_end_clean();
header("Connection: close\r\n");
header("Content-Encoding: none\r\n");
header("Content-Length: 0");
ignore_user_abort(true);
*/

// continue

require('../../template/config.php');
if ($_GET['code'] !== API_SECRET) {
        http_response_code(401);
        die();
}

require('../phone-numbers-config.php');

function dial_attempt($phone_number) {
	$ch = curl_init();

	$post = array(
		'Url' => 'https://' . $_SERVER['SERVER_NAME'] . '/twilio/find-first/call-challenge.php?code=' . API_SECRET,
		'To' => $phone_number,
		'From' => PHONE_NUMBER
	);

	curl_setopt($ch, CURLOPT_URL, 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Calls.json');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('Error making twilio call request: ' . curl_error($ch));
	}
	curl_close($ch);

	$data = json_decode($result);
	
	error_log("DIAL ATTEMPT: " . var_export($result, true));

	return $data->sid;
}

function call_completed($sid) {

        $status = call_status($sid);

		$final_status = array('busy', 'no-answer', 'canceled', 'failed', 'completed');
	
		if (in_array(strtolower($status), $final_status)) {
			// the call was ended, probably didn't complete the call challenge successfully
			return true;
		}
        return false;
}

function call_status($sid) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Calls/' . $sid . '.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
                error_log('ERROR (curl) when getting message SID info: ' . curl_error($ch));
        }
        curl_close($ch);

        $data = json_decode($result);
	
		error_log("OUTGOING CALL STATUS: " . $data);
	
		return $data->status;
}

function queue_size($queue_sid = TWILIO_FIND_FIRST_QUEUE_SID) {
	$ch = curl_init();

	$post = array(
		'FriendlyName' => TWILIO_FIND_FIRST_QUEUE
	);

	curl_setopt($ch, CURLOPT_URL, 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Queues/' . $queue_sid . '.json');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);

	$result = curl_exec($ch);
	if (curl_errno($ch)) {
		error_log('Error making twilio queue log request: ' . curl_error($ch));
	}
	curl_close($ch);

	$data = json_decode($result);

	//error_log("QUEUE SIZE: " . intval($data->current_size));

	return intval($data->current_size);
}

$incoming_sid = $_GET['SID'];

sleep(1); // give the queue a chance to register

$max_attempts = 5;
$i = 1;

while ($i <= $max_attempts) {
    foreach (constant('PHONE_NUMBERS') as $phone_number) {
        if (queue_size() > 0) {
            $outgoing_sid = dial_attempt($phone_number);
            do {
                error_log("INCOMING CALL STATUS: " . call_status($incoming_sid));
                sleep(1);
            } while (!call_completed($outgoing_sid) && queue_size() > 0);
        } else {
            break 2;
        }
    }
    $i++;
}

error_log("INCOMING CALL STATUS LAST: " . call_status($incoming_sid));