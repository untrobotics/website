<?php
function handle_payment_notification($ipn, $payment_info, $custom) {
	$name = $payment_info->options[2][1];
	$email = $payment_info->options[1][1];
	$euid = $payment_info->options[0][1];
	
	$amount = $payment_info->mc_gross;
	$fee = $payment_info->mc_fee;
	$txid =  $payment_info->txn_id;

	if ($amount > 0) {
		$db->query('INSERT INTO dues_payments (name, email, euid, amount, fee, txid)
		VALUES (
		"' . $db->real_escape_string($name) . '",
		"' . $db->real_escape_string($email) . '",
		"' . $db->real_escape_string($euid) . '",
		"' . $db->real_escape_string($amount) . '",
		"' . $db->real_escape_string($fee) . '",
		"' . $db->real_escape_string(txid) . '"
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
	} else {
		// alert! this is some type of reversal
	}
}