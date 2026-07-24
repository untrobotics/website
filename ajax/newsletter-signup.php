<?php
require('../template/top.php');
require('../api/discord/bots/admin.php');

// Best-effort add to the Brevo contact list. Upserts (updateEnabled) so a repeat
// sign-up doesn't error, and re-subscribes anyone who had been removed. Returns
// silently on any failure — the newsletter_signups row is the source of truth and
// the sign-up must not fail because Brevo is briefly unreachable.
function brevo_subscribe($email) {
	if (!defined('BREVO_API_KEY') || BREVO_API_KEY === '' || !BREVO_NEWSLETTER_LIST_ID) {
		return;
	}
	$ch = curl_init('https://api.brevo.com/v3/contacts');
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('api-key: ' . BREVO_API_KEY, 'Content-Type: application/json', 'accept: application/json'));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
		'email' => $email,
		'listIds' => array(BREVO_NEWSLETTER_LIST_ID),
		'updateEnabled' => true,
	)));
	$resp = curl_exec($ch);
	$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	// 201 created, 204 updated — anything else is a soft failure worth logging.
	if ($code !== 201 && $code !== 204) {
		error_log('brevo subscribe failed (' . $code . '): ' . $resp);
	}
}

// The homepage subscribe form (theme rd-mailform) posts here and shows the
// message the theme maps from the echoed code: SUCCESS / INVALID_EMAIL / ERROR.
if (isset($_POST['email'])) {
	$email = trim(@$_POST['email']);

	// Honeypot: a hidden field real users never fill. Bots that fill every field
	// out themselves; pretend success so they don't learn they were caught.
	if (!empty($_POST['website'])) {
		echo 'SUCCESS';
		exit;
	}

	// reCAPTCHA (same v2 checkbox the contact form uses). Blocks the bot spam that
	// was POSTing straight to this endpoint.
	$captcha = @$_POST['g-recaptcha-response'];
	if (empty($captcha)) {
		echo 'CAPTCHA';
		exit;
	}
	$verify = @json_decode(@file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . GOOGLE_RECAPTCHA_KEY . '&response=' . urlencode($captcha) . '&remoteip=' . urlencode($_SERVER['REMOTE_ADDR'])), true);
	if (empty($verify['success'])) {
		echo 'CAPTCHA';
		exit;
	}

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
		brevo_subscribe($email);
		if ($db->affected_rows > 0) {
			AdminBot::send_message('Newsletter sign-up: ' . $email);
		}
	} else {
		error_log('newsletter-signup failed: ' . $db->error);
		echo 'ERROR';
	}
}
