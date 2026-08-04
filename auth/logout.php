<?php
require("../template/top.php");
if (isset($_COOKIE[COOKIE_PREFIX . '_SESSION_ID']) || isset($_COOKIE[COOKIE_PREFIX . '_SESSION_NAME'])) {
	setcookie(COOKIE_PREFIX . '_SESSION_ID', false, 1, '/', WEBSITE_DOMAIN, true, true);
	setcookie(COOKIE_PREFIX . '_SESSION_NAME', false, 1, '/', WEBSITE_DOMAIN, true, true);

	$db->query("DELETE FROM auth_sessions WHERE session_id = '" . $db->real_escape_string($_COOKIE[COOKIE_PREFIX . '_SESSION_ID']) . "' OR session_name = '" . $db->real_escape_string($_COOKIE[COOKIE_PREFIX . '_SESSION_NAME']) . "' LIMIT 1");
}

session_regenerate_id();
session_unset();
session_destroy();

head('Logged Out', true);
?>
<?php
result_card([
    'status'   => 'success',
    'title'    => 'Logged Out',
    'subtitle' => "You've been signed out.",
    'lead'     => 'Thanks for stopping by &mdash; see you next time!',
    'button'   => ['href' => '/auth/login', 'label' => 'Log back in'],
]);

footer();
