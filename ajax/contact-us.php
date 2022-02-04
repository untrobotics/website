<?php
require('../template/top.php');
require('../api/discord/bots/admin.php');

if (isset($_POST)) {
	$name = @$_POST['name'];
	$email = @$_POST['email'];
	$phone = preg_replace('/[^0-9]/', '', @$_POST['phone']);
	$company = @$_POST['company'];
	$message = @$_POST['message'];
	$captcha = @$_POST['g-recaptcha-response'];
	
	if (empty($company)) {
		$company = "NONE.";
	}
	
	do {
		$response = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . GOOGLE_RECAPTCHA_KEY . '&response='.$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']), true);
		if ($response['success'] == false) {
			echo 'FAILED';
			break;
		} else if (strlen($name) < 4) {
			echo 'INVALID_NAME';
			break;
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			echo 'INVALID_EMAIL';
			break;
		} else if (strlen($phone) != 10) {
			echo 'INVALID_PHONE';
			break;
		} else if (strlen($message) < 10) {
			echo 'INVALID_MESSAGE';
			break;
		} else if (empty($captcha)) {
			echo 'EMPTY_CAPTCHA';
			break;
		}


		$q = $db->query('INSERT INTO contact_form (name, email, phone, company, message)
		VALUES (
			"' . $db->real_escape_string($name) . '",
			"' . $db->real_escape_string($email) . '",
			"' . $db->real_escape_string($phone) . '",
			"' . $db->real_escape_string($company) . '",
			"' . $db->real_escape_string($message) . '"
		)
		');
		if ($q) {
			echo 'CONTACT_SUCCESS';
			AdminBot::send_message('Received contact form message:' . "\n" . 'FROM: ' . $name . ' (' . $email . ' / ' . $phone . ')' . "\n" . 'COMPANY: ' . $company . "\n\n" . 'MESSAGE: ' . $message);
		} else {
			echo 'ERROR';
		}
	} while (false);
}

?>
