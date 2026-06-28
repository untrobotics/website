<?php
require('../template/top.php');
header('Content-Type: application/json');

function respond($ok, $message) {
    echo json_encode(array('ok' => $ok, 'message' => $message));
    exit;
}

$auth_result = auth(1);
if (!is_array($auth_result)) {
    http_response_code(401);
    respond(false, 'You must be logged in to change your password.');
}
$userinfo = $auth_result[0];
$uid = $userinfo['id'];

// CSRF
if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(400);
    respond(false, 'Your session expired. Please refresh the page and try again.');
}

$current = (string)@$_POST['current_password'];
$new     = (string)@$_POST['new_password'];
$confirm = (string)@$_POST['confirm_password'];

if (!password_verify($current, $userinfo['password'])) {
    respond(false, 'Your current password is incorrect.');
}
if (strlen($new) < 8) {
    respond(false, 'Your new password must be at least 8 characters long.');
}
if ($new !== $confirm) {
    respond(false, 'The new passwords do not match.');
}
if ($new === $current) {
    respond(false, 'Please choose a password different from your current one.');
}

$hash = password_hash($new, PASSWORD_BCRYPT, array('cost' => 12));
$q = $db->query('UPDATE users SET
        password = "' . $db->real_escape_string($hash) . '",
        reg_timestamp = reg_timestamp
    WHERE id = "' . $db->real_escape_string($uid) . '"');

if ($q) {
    respond(true, 'Your password has been changed.');
}
error_log('update-password failed for uid ' . $uid . ': ' . $db->error);
respond(false, 'A server error occurred. Please try again later.');
