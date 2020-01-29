<?php

$log = var_export($_REQUEST, true);

function payment_log($message) {
	file_put_contents('logs/ipn.log', $message, FILE_APPEND);
}

require('verify_ipn.php');
require('../template/top.php');

$ipn = new PaypalIPN();
if (is_sandbox()) {
        $ipn->useSandbox();
}

//log_payment($invoice_id, 'INFO: IPN, SANDBOX: ' . (is_sandbox() ? 'true' : 'false'));

$verified = $ipn->verifyIPN();
if ($verified) {
	// verified
	payment_log("VALID");
	payment_log(var_export($_REQUEST, true));

	$custom = $_POST['custom'];
	$amount = $_POST['payment_gross'];
	$fee = $_POST['payment_fee'];
	$txid = $_POST['txn_id'];

	if ($custom == 'MERCH_CLUB_TSHIRT') {
		
	} else if ($custom == 'DUES_PAYMENT') {
		$name = $_POST['option_selection3'];
		$email = $_POST['option_selection2'];
		$euid = $_POST['option_selection1'];

		$db->query('INSERT INTO dues_payments (name, email, euid, amount, fee, txid)
		VALUES (
		"' . $db->real_escape_string($name) . '",
		"' . $db->real_escape_string($email) . '",
		"' . $db->real_escape_string($euid) . '",
		"' . $db->real_escape_string($amount) . '",
		"' . $db->real_escape_string($fee) . '",
		"' . $db->real_escape_string($txid) . '"
		)');

		email($email, "UNT Robotics Dues Payment Receipt",
			  "Dear {$name},<br>
			  <br>
			  <strong>Thank for paying your UNT Robotics dues.</strong><br>
			  This is your receipt confirming we have received your payment and logged it in our system.<br>
			  <br>
			  Transaction amount: <pre>{$amount}</pre><br>
			  Transaction PayPal ID: <pre>{$txid}</pre><br>
			  <br>
			  <br>
			  If you have any concerns or questions, please feel free to reach out to us at <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a>
			  <br>
			  <br>
			  Kindest regards,<br>
			  UNT Robotics Officers
		  ");
	}
} else {
	// NOT verified
	payment_log("INVALID");
}
