<?php
/**
 * Single internal email-send path.
 *
 * Every in-cluster service that needs to send mail (currently the Discord bot)
 * POSTs here instead of implementing its own SMTP / Brevo / failover / alerting.
 * ALL of that lives in ONE place: email() in template/top.php — the Brevo
 * smarthost with Postfix failover, the branded template, and the admin-channel
 * alert on Brevo failure. This endpoint is a thin, authenticated wrapper.
 *
 * Gated by a shared secret (INTERNAL_EMAIL_SECRET) so it is not an open relay.
 * Reachable only inside the cluster (no ingress route); callers use the internal
 * service DNS, e.g. http://web/api/internal/send-email.php.
 */

require('../../template/top.php');

header('Content-Type: application/json');

// --- Fail-closed shared-secret gate -----------------------------------------
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
if (!is_array($data) || empty($data['to']) || !isset($data['subject']) || !isset($data['body'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'to, subject and body are required'));
    exit;
}

// Callers pass just the inner content; email() applies the branded template
// unless branded=false is explicitly set.
$ok = email(
    $data['to'],
    (string) $data['subject'],
    (string) $data['body'],
    isset($data['replyto']) ? $data['replyto'] : false,
    null,
    array(),
    isset($data['branded']) ? (bool) $data['branded'] : true
);

echo json_encode(array('sent' => (bool) $ok));
