<?php
/**
 * URW-208: internal-only endpoint that SYNCHRONOUSLY refreshes a single cached
 * API entry. Invoked fire-and-forget by api/api-cache.php's stale-while-
 * revalidate path (trigger_cache_refresh()). Not exposed to the internet — the
 * /api/internal/ prefix has no ingress route (see .htaccess) — and additionally
 * gated by the shared API_SECRET below.
 *
 * Defining CACHE_FORCE_SYNC BEFORE api-cache.php loads makes get_valid_cache_entry
 * skip its own SWR shortcut and do the blocking fetch + cache write, so this
 * request is what actually updates the cache.
 */
define('CACHE_FORCE_SYNC', true);

require_once(__DIR__ . '/../../template/top.php');
require_once(__DIR__ . '/../api-cache.php');

// Finish the refresh even after the caller (which uses a tiny timeout) hangs up.
ignore_user_abort(true);

header('Content-Type: application/json');

// --- Auth: internal, secret-gated only ------------------------------------
$provided = isset($_POST['secret']) ? $_POST['secret'] : (isset($_GET['secret']) ? $_GET['secret'] : '');
if (!defined('API_SECRET') || !API_SECRET || !hash_equals((string) API_SECRET, (string) $provided)) {
    http_response_code(403);
    echo json_encode(array('error' => 'forbidden'));
    exit;
}

$endpoint = isset($_POST['endpoint']) ? $_POST['endpoint'] : (isset($_GET['endpoint']) ? $_GET['endpoint'] : '');
$args_raw = isset($_POST['args']) ? $_POST['args'] : (isset($_GET['args']) ? $_GET['args'] : 'a:0:{}');
if ($endpoint === '') {
    http_response_code(400);
    echo json_encode(array('error' => 'missing endpoint'));
    exit;
}

// SSRF guard: only refresh endpoints that already have a cache config row. This
// keeps the fetch bound to the configured (Printful) endpoints — an attacker with
// the secret still can't point it at an arbitrary host.
if (get_config_id($endpoint) === false) {
    http_response_code(404);
    echo json_encode(array('error' => 'unknown endpoint'));
    exit;
}

// Scalars only — never instantiate objects from the incoming serialized args.
$args = @unserialize((string) $args_raw, array('allowed_classes' => false));
if (!is_array($args)) {
    http_response_code(400);
    echo json_encode(array('error' => 'bad args'));
    exit;
}

// Only cached calls are GETs (POSTs to Printful aren't cached), so rebuild an
// authed Printful GET handle. If another cacheable API is added later, this is
// the one spot that would need to branch on the endpoint host.
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . PRINTFUL_API_KEY));
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

// CACHE_FORCE_SYNC is defined, so this blocks on the fetch and writes the cache.
get_valid_cache_entry($endpoint, $ch, ...$args);
curl_close($ch);

http_response_code(204);
