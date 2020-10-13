<?php
require('../template/top.php');
require('../api/groupme-funcs.php');

if (isset($_POST)) {
	$name = $_POST['name'];
	$email = $_POST['email'];
	$phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
	$company = $_POST['company'];
	$message = $_POST['message'];
	
	if (empty($company)) {
		$company = "NONE.";
	}
	
	do {
		if (strlen($name) < 4) {
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
			post_message('Received contact form message:' . "\n" . 'FROM: ' . $name . ' (' . $email . ' / ' . $phone . ')' . "\n" . 'COMPANY: ' . $company . "\n\n" . 'MESSAGE: ' . $message, GROUPME_OFFICER_CHANNEL_ID);
		} else {
			echo 'ERROR';
		}
	} while (false);
}

?>
