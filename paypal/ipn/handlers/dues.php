<?php
function handle_payment_notification($ipn, $payment_info, $custom) {
	global $db;
	
	$dues_term = $payment_info->options[0][1];
	$dues_year = $payment_info->options[1][1];
	
	$term_string = ucfirst(strtolower($dues_term)) . ' ' . $dues_year;
	
	$amount_paid = $payment_info->mc_gross;
	$fee = $payment_info->mc_fee;
	$txid =  $payment_info->txn_id;
	
	$uid = $custom['uid'];
	
	$q = $db->query('SELECT * FROM users WHERE id = "' . $db->real_escape_string($uid) . '"');
	if (!$q) {
		throw new IPNHandlerException("[{$payment_info->txn_id}]: Unable to retrieve matching user from database for dues payment (uid: {$uid}) (error: {$db->error})");
	}
	if ($q->num_rows !== 1) {
		throw new IPNHandlerException("[{$payment_info->txn_id}]: Query for matching user for dues payment returned non-singular result (uid: {$uid}) (count: {$q->num_rows})");
	}
	$r = $q->fetch_array(MYSQLI_ASSOC);
	
	// get the dues payment amount from the data
	// confirm the addition of the dues_payment row
	// confirm the submission of the e-mail

	if ($amount_paid > 0) {
		$db->query('INSERT INTO dues_payments (name, email, euid, amount, fee, txid)
		VALUES (
		"' . $db->real_escape_string($r['name']) . '",
		"' . $db->real_escape_string($r['email']) . '",
		"' . $db->real_escape_string($r['unteuid']) . '",
		"' . $db->real_escape_string($amount_paid) . '",
		"' . $db->real_escape_string($fee) . '",
		"' . $db->real_escape_string($txid) . '"
		)');

		$email_send_status = email(
			$payment_info->payer_email,
			"Receipt for your UNT Robotics dues payment",
			
			"<div style=\"position: relative;max-width: 100vw;text-align:center;\">" .
			'<img src="cid:untrobotics-email-header">' .
			
			'	<div></div>' .
			
			'<div style="text-align: left; max-width: 500px; display: inline-block;">' .
			"	<p>Dear " . $payment_info->first_name . ' ' . $payment_info->last_name . ",</p>" .
			"	<p>Thank you for paying your UNT Robotics dues. If you have not yet received the <em>Good Standing</em> role in the Discord server, please go to <a href=\"https://untro.bo/join/discord\">untro.bo/join/discord</a> to be automatically assigned the role.</p>" .
			'</div>' .
			
			'	<div></div>' .
			
			"	<div style=\"display: inline-block;padding: 15px;border: 1px solid #bdbdbd;border-radius: 10px;text-align: left;\">" .
			"		<h5 style=\"font-size: 12pt;margin: 0;font-weight: 600;\">ðŸ§¾ Payment Receipt</h5>" .
			"		<ul>" .
			"			<li><strong>PayPal Transaction ID</strong> <a href=\"https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id={$payment_info->txn_id}\">{$payment_info->txn_id}</a></li>" .
			"			<li><strong>Date/Time</strong> " . date('l jS \of F Y h:i:s A T', strtotime($payment_info->payment_date)) . "</li>" .
			"			<li><strong>Payment Amount</strong> \${$amount_paid}</li>" .
			"			<li><strong>Name</strong> {$payment_info->first_name} {$payment_info->last_name}</li>" .
			"			<li><strong>Order Name</strong> {$payment_info->item_name}</li>" .
			"			<li><strong>Semester</strong> {$term_string}</li>" .
			"		</ul>" .
			"	</div>" .
			"	<p></p>" .
			"	<p>If you need any assistance with your payment or with receiving the correct role, please reach out to <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a> or contact us <a href=\"" . DISCORD_INVITE_URL . "\">on Discord</a>.</p>" .
			"</div>",
			
			false,
			null,
			[
				[
					'content' => base64_encode(file_get_contents('../images/unt-robotics-email-header.jpg')),
					'type' => 'image/jpeg',
					'filename' => 'unt-robotics-email-header.jpg',
					'disposition' => 'inline',
					'content_id' => 'untrobotics-email-header'
				]
			]
		);
		
		if ($email_send_status) {
			payment_log("[{$payment_info->txn_id}] Successfully sent e-mail receipt (" . var_export($email_send_status, true) . ")");
		} else {
			//throw new IPNHandlerException("[{$payment_info->txn_id}]: Failed to send e-mail receipt (" . var_export($email_send_status, true) . ")");
			payment_log("[{$payment_info->txn_id}] Failed to send e-mail receipt (" . var_export($email_send_status, true) . ")");
			AdminBot::send_message("(IPN) Alert: Failed to send e-mail receipt for dues payment [{$payment_info->txn_id}].");
		}
		
	} else {
		// alert! this is some type of reversal
	}
}