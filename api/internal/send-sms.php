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
// which only resolves from api/. Switch to that working directory so the
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

// Optional MMS media: an array of publicly-fetchable https URLs (e.g. Discord
// CDN attachment URLs). Twilio fetches each MediaUrl itself; it accepts at most
// 10 media per message, so cap the list.
$media = array();
if (is_array($data) && isset($data['media']) && is_array($data['media'])) {
    foreach ($data['media'] as $url) {
        $url = trim((string) $url);
        if ($url !== '' && preg_match('#^https://#i', $url)) {
            $media[] = $url;
        }
        if (count($media) >= 10) {
            break;
        }
    }
}

// A message needs a body OR at least one media attachment (or both).
$body = (is_array($data) && isset($data['body'])) ? trim((string) $data['body']) : '';
if (!is_array($data) || empty($data['to']) || ($body === '' && empty($media))) {
    http_response_code(400);
    echo json_encode(array('error' => 'to and (body or media) are required'));
    exit;
}

// Validate the destination before it reaches the Twilio API.
$to = trim($data['to']);
if (!preg_match('/^\+?[0-9]{10,15}$/', $to)) {
    http_response_code(400);
    echo json_encode(array('error' => 'invalid destination number'));
    exit;
}

// Optional consent gate. Proactive/marketing callers pass respect_consent=true,
// which refuses to text a registered user who hasn't opted in (users.sms_consent).
// Transactional sends — one-time verification codes, and officer replies to an
// inbound text — omit it: those are user-initiated and allowed regardless.
if (!empty($data['respect_consent'])) {
    $last10 = substr(preg_replace('/[^0-9]/', '', $to), -10);
    $cq = $db->query('SELECT sms_consent FROM users WHERE RIGHT(phone, 10) = "' . $db->real_escape_string($last10) . '" LIMIT 1');
    if ($cq && $cq->num_rows > 0 && (int) $cq->fetch_assoc()['sms_consent'] !== 1) {
        error_log('[sms] blocked by consent gate (recipient has not opted in): to=' . $to);
        echo json_encode(array('status' => 'blocked_no_consent'));
        exit;
    }
}

$status = send_sms_message($body, $to, $media);
// Audit every send (initial status only; the final delivery status arrives via
// twilio/sms-status.php). Goes to the Apache log -> Discord web-logs channel.
error_log('[sms] internal send to=' . $to . ' media=' . count($media) . ' -> ' . var_export($status, true));
echo json_encode(array('status' => $status));
