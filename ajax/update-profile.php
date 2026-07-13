<?php
require('../template/top.php');
header('Content-Type: application/json');

function respond($ok, $message) {
    echo json_encode(array('ok' => $ok, 'message' => $message));
    exit;
}

// Trust the user id only from the authenticated session, never from POST.
$auth_result = auth(1);
if (!is_array($auth_result)) {
    http_response_code(401);
    respond(false, 'You must be logged in to update your profile.');
}
$userinfo = $auth_result[0];
$uid = $userinfo['id'];

// CSRF
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(400);
    respond(false, 'Your session expired. Please refresh the page and try again.');
}

$name     = trim(@$_POST['name']);
$email    = trim(@$_POST['email']);
$phone    = preg_replace('/[^0-9]/', '', @$_POST['phone']);
$timezone = (string)@$_POST['timezone'];
$grad_term = @$_POST['grad_term'];
$grad_year = @$_POST['grad_year'];
$sms_consent = isset($_POST['sms_consent']) ? 1 : 0;

global $timezones; // timezone_identifiers_list(), defined in top.php
if (empty($timezones)) $timezones = timezone_identifiers_list();

// Validate (mirrors auth/join.php rules)
if (strlen($name) < 4) {
    respond(false, 'Please enter your full name (at least 4 characters).');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
if (strlen($phone) != 10) {
    respond(false, 'Please enter a valid 10-digit U.S. phone number.');
}
if (!in_array((int)$grad_term, array(0, 1, 2), true)) {
    respond(false, 'Please choose a valid graduation term.');
}
$grad_term = (int)$grad_term;
if ((int)$grad_year < (int)date('Y')) {
    respond(false, 'Your graduation year cannot be in the past.');
}
$grad_year = (int)$grad_year;
if (!in_array($timezone, $timezones, true)) {
    respond(false, 'Please choose a valid timezone.');
}

// Email must be unique across other accounts.
$er = $db->query('SELECT id FROM users WHERE email = "' . $db->real_escape_string($email) . '" AND id != "' . $db->real_escape_string($uid) . '" LIMIT 1');
if ($er && $er->num_rows > 0) {
    respond(false, 'That email address is already in use by another account.');
}

// reg_timestamp = reg_timestamp prevents the column's ON UPDATE CURRENT_TIMESTAMP
// from resetting the "member since" date on every edit.
$q = $db->query('UPDATE users SET
        name = "' . $db->real_escape_string($name) . '",
        email = "' . $db->real_escape_string($email) . '",
        phone = "' . $db->real_escape_string($phone) . '",
        grad_term = "' . $db->real_escape_string($grad_term) . '",
        grad_year = "' . $db->real_escape_string($grad_year) . '",
        timezone = "' . $db->real_escape_string($timezone) . '",
        sms_consent = ' . intval($sms_consent) . ',
        reg_timestamp = reg_timestamp
    WHERE id = "' . $db->real_escape_string($uid) . '"');

if ($q) {
    respond(true, 'Your profile has been updated.');
}
error_log('update-profile failed for uid ' . $uid . ': ' . $db->error);
respond(false, 'A server error occurred. Please try again later.');
