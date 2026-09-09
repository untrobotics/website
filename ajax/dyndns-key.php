<?php
/**
 * Self-service dyndns key for admins. A logged-in admin (is_admin) generates or
 * regenerates their own personal key here; the key can set any *.dyndns name (it
 * carries no subdomain restrictions). One key per member is enforced by the
 * UNIQUE(uid) index, so regenerating replaces the previous value. Admin-only:
 * the gate is enforced server-side, not just hidden in the UI.
 */
require('../template/top.php');
header('Content-Type: application/json');

function respond($ok, $message, $extra = array()) {
    echo json_encode(array_merge(array('ok' => $ok, 'message' => $message), $extra));
    exit;
}

// Admin session only — trust the uid from the session, never from POST.
$auth_result = auth(2);
if (!is_array($auth_result)) {
    http_response_code(403);
    respond(false, 'Only admins can manage a dynamic DNS key.');
}
$userinfo = $auth_result[0];
$uid = (int) $userinfo['id'];

if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    http_response_code(400);
    respond(false, 'Your session expired. Please refresh the page and try again.');
}

$action = isset($_POST['action']) ? $_POST['action'] : 'generate';
if ($action !== 'generate') {
    respond(false, 'Unknown action.');
}

$key  = bin2hex(random_bytes(16)); // 128-bit, fits varchar(40)
$desc = 'Personal key — ' . $userinfo['name'];

// Create this member's key, or replace its value if they already have one.
$st = $db->prepare(
    'INSERT INTO dyndns_api_keys (api_key_value, description, subdomain_restrictions, uid)
     VALUES (?, ?, NULL, ?)
     ON DUPLICATE KEY UPDATE api_key_value = VALUES(api_key_value), description = VALUES(description)'
);
$st->bind_param('ssi', $key, $desc, $uid);
if (!$st->execute()) {
    http_response_code(500);
    respond(false, 'Could not save your key. Please try again.');
}

$super  = DYNDNS_ALLOWED_SUPERDOMAINS[0];
$suffix = DYNDNS_FORCE_SUBDOMAIN;
$url = 'https://' . $super . '/dyndns/api/ip2host.php?API_KEY=' . $key
     . '&super_domain=' . $super . '&sub_domain=YOURNAME.' . $suffix;

respond(true, 'Your dynamic DNS key has been generated. Copy it now — regenerating will replace it.', array(
    'key' => $key,
    'url' => $url,
));
