<?php
/**
 * Single internal calendar-add path.
 *
 * The Discord /addevent command (Officers only) POSTs here instead of talking
 * to Google directly, so the Google Calendar service-account credentials live
 * in ONE place (the app secret set) — the same thin-bot pattern as
 * api/internal/send-email.php.
 *
 * Auth: shared secret (INTERNAL_EMAIL_SECRET) via X-Internal-Secret. Reachable
 * only inside the cluster (no ingress route); the bot uses the internal service
 * DNS, e.g. http://web/api/internal/calendar-add-event.php.
 *
 * Google auth: a service account (GOOGLE_CALENDAR_SA_EMAIL + _PRIVATE_KEY). The
 * target calendar (GOOGLE_CALENDAR_ID) must be shared with that SA email as
 * "Make changes to events".
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

// --- Service-account config -------------------------------------------------
if (!defined('GOOGLE_CALENDAR_SA_EMAIL') || GOOGLE_CALENDAR_SA_EMAIL === ''
    || !defined('GOOGLE_CALENDAR_SA_PRIVATE_KEY') || GOOGLE_CALENDAR_SA_PRIVATE_KEY === '') {
    fail(503, 'calendar not configured');
}
$calendarId = defined('GOOGLE_CALENDAR_ID') && GOOGLE_CALENDAR_ID !== '' ? GOOGLE_CALENDAR_ID : 'untroboticsclub@gmail.com';

// --- Parse + validate input -------------------------------------------------
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || empty($data['title']) || empty($data['date'])) {
    fail(400, 'title and date are required');
}

$title    = trim((string) $data['title']);
$date     = trim((string) $data['date']);
$allDay   = !empty($data['allDay']);
$location = isset($data['location']) ? trim((string) $data['location']) : '';
$descIn   = isset($data['description']) ? trim((string) $data['description']) : '';
$tz       = isset($data['timezone']) && $data['timezone'] !== '' ? (string) $data['timezone'] : 'America/Chicago';
$by       = isset($data['requestedBy']) ? trim((string) $data['requestedBy']) : '';

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    fail(400, 'date must be YYYY-MM-DD');
}
$dt = DateTime::createFromFormat('Y-m-d', $date);
if (!$dt || $dt->format('Y-m-d') !== $date) {
    fail(400, 'invalid date');
}

$event = array('summary' => $title);
if ($location !== '') $event['location'] = $location;
$desc = $descIn;
if ($by !== '') $desc = ($desc === '' ? '' : $desc . "\n\n") . 'Added via Discord by ' . $by;
if ($desc !== '') $event['description'] = $desc;

if ($allDay) {
    // Google all-day end.date is exclusive → next day.
    $end = (clone $dt)->modify('+1 day');
    $event['start'] = array('date' => $dt->format('Y-m-d'));
    $event['end']   = array('date' => $end->format('Y-m-d'));
} else {
    if (empty($data['start']) || empty($data['end'])) {
        fail(400, 'start and end are required for a timed event');
    }
    if (!preg_match('/^\d{2}:\d{2}$/', $data['start']) || !preg_match('/^\d{2}:\d{2}$/', $data['end'])) {
        fail(400, 'start/end must be HH:MM');
    }
    // Local wall-clock times + timeZone; Google resolves the offset (handles DST).
    $event['start'] = array('dateTime' => $date . 'T' . $data['start'] . ':00', 'timeZone' => $tz);
    $event['end']   = array('dateTime' => $date . 'T' . $data['end'] . ':00', 'timeZone' => $tz);
}

// --- Mint a service-account access token (JWT bearer grant) ------------------
function b64url($bin) {
    return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
}

function calendar_access_token($saEmail, $privateKeyPem, &$err) {
    $now = time();
    $header = b64url(json_encode(array('alg' => 'RS256', 'typ' => 'JWT')));
    $claim = b64url(json_encode(array(
        'iss'   => $saEmail,
        'scope' => 'https://www.googleapis.com/auth/calendar.events',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'iat'   => $now,
        'exp'   => $now + 3600,
    )));
    $signingInput = $header . '.' . $claim;
    $signature = '';
    $key = openssl_pkey_get_private($privateKeyPem);
    if ($key === false) { $err = 'bad service-account private key'; return null; }
    if (!openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
        $err = 'jwt signing failed';
        return null;
    }
    $jwt = $signingInput . '.' . b64url($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt_array($ch, array(
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_POSTFIELDS => http_build_query(array(
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        )),
    ));
    $resp = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($resp === false) { $err = 'token request failed: ' . curl_error($ch); curl_close($ch); return null; }
    curl_close($ch);
    $json = json_decode($resp, true);
    if ($status !== 200 || empty($json['access_token'])) {
        $err = 'token exchange failed' . (isset($json['error']) ? ': ' . $json['error'] : '');
        return null;
    }
    return $json['access_token'];
}

$pem = str_replace('\\n', "\n", GOOGLE_CALENDAR_SA_PRIVATE_KEY);
$err = '';
$token = calendar_access_token(GOOGLE_CALENDAR_SA_EMAIL, $pem, $err);
if (!$token) {
    error_log('calendar-add-event: ' . $err);
    fail(502, $err !== '' ? $err : 'auth failed');
}

// --- Insert the event -------------------------------------------------------
$url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events';
$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => array(
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ),
    CURLOPT_POSTFIELDS => json_encode($event),
));
$resp = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($resp === false) {
    $e = curl_error($ch);
    curl_close($ch);
    error_log('calendar-add-event: insert failed: ' . $e);
    fail(502, 'calendar request failed');
}
curl_close($ch);

$json = json_decode($resp, true);
if ($status < 200 || $status >= 300 || empty($json['id'])) {
    $msg = isset($json['error']['message']) ? $json['error']['message'] : ('HTTP ' . $status);
    error_log('calendar-add-event: insert rejected: ' . $msg);
    fail(502, $msg);
}

echo json_encode(array(
    'ok'       => true,
    'id'       => $json['id'],
    'htmlLink' => isset($json['htmlLink']) ? $json['htmlLink'] : null,
));
