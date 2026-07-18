<?php
require('../template/top.php');
require('../api/discord/bots/admin.php');

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
		if ($db->affected_rows > 0) {
			AdminBot::send_message('Newsletter sign-up: ' . $email);
		}
	} else {
		error_log('newsletter-signup failed: ' . $db->error);
		echo 'ERROR';
	}
}
