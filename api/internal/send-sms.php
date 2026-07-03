<?php
/**
 * Single internal SMS-send path (mirrors api/internal/send-email.php).
 *
 * The Discord bot POSTs here when an officer replies to an incoming-SMS message
 * in the admin channel. Gated by the shared internal secret; reachable only
 * inside the cluster (no ingress route — see the /api/internal/ exemption in
 * .htaccess). All the Twilio plumbing stays in send_sms_message().
 */

require('../../template/top.php');
// twilio-send.php loads config with a CWD-relative require("../template/config.php"),
// which only resolves from api/. Match groupme-bot.php's working directory so the
// include succeeds (config is already loaded via top.php, so it's a no-op).
chdir(BASE . '/api');
require_once(BASE . '/api/twilio-send.php');

header('Content-Type: application/json');

$provided = isset($_SERVER['HTTP_X_INTERNAL_SECRET']) ? $_SERVER['HTTP_X_INTERNAL_SECRET'] : '';
if (!defined('INTERNAL_EMAIL_SECRET') || INTERNAL_EMAIL_SECRET === '' || !hash_equals(INTERNAL_EMAIL_SECRET, $provided)) {
    http_response_code(403);
    echo json_encode(array('error' => 'forbidden'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'method not allowed'));
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['to']) || !isset($data['body']) || trim((string) $data['body']) === '') {
    http_response_code(400);
    echo json_encode(array('error' => 'to and body are required'));
    exit;
}

// Validate the destination before it reaches the Twilio API.
$to = trim($data['to']);
if (!preg_match('/^\+?[0-9]{10,15}$/', $to)) {
    http_response_code(400);
    echo json_encode(array('error' => 'invalid destination number'));
    exit;
}

$status = send_sms_message((string) $data['body'], $to, array());
// Audit every send (initial status only; the final delivery status arrives via
// twilio/sms-status.php). Goes to the Apache log -> Discord web-logs channel.
error_log('[sms] internal send to=' . $to . ' -> ' . var_export($status, true));
echo json_encode(array('status' => $status));
