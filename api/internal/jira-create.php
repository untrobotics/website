<?php
/**
 * Single internal Jira-create path. The Discord /jira command (officers only)
 * POSTs here instead of talking to Jira directly, so the Jira API credentials
 * live in ONE place (the app secret set) — the same thin-bot pattern as
 * calendar-add-event.php / send-email.php.
 *
 * Auth: shared secret (INTERNAL_EMAIL_SECRET) via X-Internal-Secret. Reachable
 * only inside the cluster (no ingress route); the bot uses the internal service
 * DNS, e.g. http://web/api/internal/jira-create.php.
 *
 * Jira auth: HTTP Basic JIRA_EMAIL:JIRA_API_TOKEN against JIRA_BASE_URL; creates
 * an issue in JIRA_PROJECT_KEY (default URW).
 */

require('../../template/top.php');

header('Content-Type: application/json');

function fail($code, $msg) {
    http_response_code($code);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

// --- Fail-closed shared-secret gate -----------------------------------------
$provided = isset($_SERVER['HTTP_X_INTERNAL_SECRET']) ? $_SERVER['HTTP_X_INTERNAL_SECRET'] : '';
if (!defined('INTERNAL_EMAIL_SECRET') || INTERNAL_EMAIL_SECRET === '' || !hash_equals(INTERNAL_EMAIL_SECRET, $provided)) {
    fail(403, 'forbidden');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'method not allowed');
}

// --- Jira config ------------------------------------------------------------
if (!defined('JIRA_BASE_URL') || JIRA_BASE_URL === ''
    || !defined('JIRA_EMAIL') || JIRA_EMAIL === ''
    || !defined('JIRA_API_TOKEN') || JIRA_API_TOKEN === '') {
    fail(503, 'jira not configured');
}
$project = defined('JIRA_PROJECT_KEY') && JIRA_PROJECT_KEY !== '' ? JIRA_PROJECT_KEY : 'URW';

// --- Parse + validate input -------------------------------------------------
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['summary'])) {
    fail(400, 'summary is required');
}
$summary   = trim((string) $data['summary']);
$descText  = isset($data['description']) ? trim((string) $data['description']) : '';
$issuetype = isset($data['issuetype']) ? trim((string) $data['issuetype']) : 'Task';
if (!in_array($issuetype, array('Task', 'Bug', 'Story'), true)) {
    $issuetype = 'Task';
}
$requestedBy = isset($data['requestedBy']) ? trim((string) $data['requestedBy']) : '';

// ADF description: the details, plus a provenance line so the board shows where
// the ticket came from.
$paras = array();
if ($descText !== '') {
    $paras[] = array('type' => 'paragraph', 'content' => array(array('type' => 'text', 'text' => $descText)));
}
if ($requestedBy !== '') {
    $paras[] = array('type' => 'paragraph', 'content' => array(array('type' => 'text', 'text' => 'Created from Discord by ' . $requestedBy . '.')));
}
if (!$paras) {
    $paras[] = array('type' => 'paragraph', 'content' => array(array('type' => 'text', 'text' => ' ')));
}

$body = array('fields' => array(
    'project'     => array('key' => $project),
    'issuetype'   => array('name' => $issuetype),
    'summary'     => mb_substr($summary, 0, 250),
    'description' => array('type' => 'doc', 'version' => 1, 'content' => $paras),
));

$ch = curl_init(rtrim(JIRA_BASE_URL, '/') . '/rest/api/3/issue');
curl_setopt_array($ch, array(
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($body),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => array(
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode(JIRA_EMAIL . ':' . JIRA_API_TOKEN),
    ),
    CURLOPT_CONNECTTIMEOUT => 15,
    CURLOPT_TIMEOUT        => 25,
));
$raw  = curl_exec($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$resp = json_decode($raw, true);

if ($code === 201 && isset($resp['key'])) {
    echo json_encode(array(
        'ok'  => true,
        'key' => $resp['key'],
        'url' => rtrim(JIRA_BASE_URL, '/') . '/browse/' . $resp['key'],
    ));
} else {
    error_log('jira-create: HTTP ' . $code . ' ' . substr((string) $raw, 0, 300));
    fail(502, 'jira api error (HTTP ' . $code . ')');
}
