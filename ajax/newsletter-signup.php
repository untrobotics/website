<?php
require('../template/top.php');
require('../api/discord/bots/admin.php');

// Best-effort add to a Mailchimp audience. Upserts the member: new addresses are
// subscribed, existing ones keep their current status (so we never resurrect an
// unsubscribe). Returns silently on any failure — the DB row is the source of
// truth and the signup must not fail because Mailchimp is down.
function mailchimp_subscribe($email) {
	if (!defined('MAILCHIMP_API_KEY') || MAILCHIMP_API_KEY === '' || MAILCHIMP_LIST_ID === '') {
		return;
	}
	$parts = explode('-', MAILCHIMP_API_KEY);
	$dc = end($parts);
	if (!$dc || $dc === MAILCHIMP_API_KEY) {
		return;
	}
	$hash = md5(strtolower($email));
	$url = 'https://' . $dc . '.api.mailchimp.com/3.0/lists/' . MAILCHIMP_LIST_ID . '/members/' . $hash;
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_USERPWD, 'anystring:' . MAILCHIMP_API_KEY);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
		'email_address' => $email,
		'status_if_new' => 'subscribed',
	)));
	$resp = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	if ($code < 200 || $code >= 300) {
		error_log('mailchimp subscribe failed (' . $code . '): ' . $resp);
	}
}

// The homepage subscribe form (theme rd-mailform) posts here and shows the
// message the theme maps from the echoed code: SUCCESS / INVALID_EMAIL / ERROR.
if (isset($_POST['email'])) {
	$email = trim(@$_POST['email']);

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo 'INVALID_EMAIL';
		exit;
	}

	// Silently succeed on a repeat sign-up (unique email) so we neither leak
	// who is subscribed nor show an error for an already-registered address.
	$q = $db->query('INSERT INTO newsletter_signups (email, ip)
		VALUES (
			"' . $db->real_escape_string($email) . '",
			"' . $db->real_escape_string($_SERVER['REMOTE_ADDR']) . '"
		)
		ON DUPLICATE KEY UPDATE id = id');

	if ($q) {
		echo 'SUCCESS';
		mailchimp_subscribe($email);
		if ($db->affected_rows > 0) {
			AdminBot::send_message('Newsletter sign-up: ' . $email);
		}
	} else {
		error_log('newsletter-signup failed: ' . $db->error);
		echo 'ERROR';
	}
}
