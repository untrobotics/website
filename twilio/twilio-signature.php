<?php
// Shared Twilio request-signature validation for the Twilio-facing endpoints.
//
// Twilio signs every webhook it sends with the account's auth token and puts the
// result in the X-Twilio-Signature header. The algorithm is:
//   1. Start with the full request URL (scheme + host + path + query) exactly as
//      Twilio called it.
//   2. If it's a POST, sort the POST params by key and append each as key+value
//      (concatenated, no separators) to that string.
//   3. HMAC-SHA1 the result with the auth token and base64-encode it.
// We recompute that and hash_equals() it against the header.
//
// SAFETY: signature enforcement only kicks in when TWILIO_AUTH_TOKEN is actually
// configured. On a deployment that hasn't set the token yet we fall back to the
// existing ?code === API_SECRET check (still performed by each endpoint) so we
// don't lock out a working install. When the token IS set, a valid signature is
// required IN ADDITION to the code check.

// Reconstruct the absolute URL of the current request as Twilio would have seen it.
function twilio_request_url() {
    $https = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
    return $scheme . '://' . $host . ($_SERVER['REQUEST_URI'] ?? '');
}

// Compute Twilio's base64 HMAC-SHA1 signature for a given URL + POST param array.
// Also usable by callers that make signed server-to-server requests.
function compute_twilio_signature($url, array $post_params) {
    ksort($post_params);
    $data = $url;
    foreach ($post_params as $key => $value) {
        $data .= $key . $value;
    }
    return base64_encode(hash_hmac('sha1', $data, TWILIO_AUTH_TOKEN, true));
}

// Returns true if the request carries a valid Twilio signature, or if no auth
// token is configured (in which case the caller's ?code check is the only gate).
function validate_twilio_signature() {
    if (!defined('TWILIO_AUTH_TOKEN') || TWILIO_AUTH_TOKEN === '') {
        return true; // token not set yet: rely on the existing ?code check
    }
    $provided = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';
    if ($provided === '') {
        return false;
    }
    $expected = compute_twilio_signature(twilio_request_url(), $_POST);
    return hash_equals($expected, $provided);
}
