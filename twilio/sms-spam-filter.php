<?php
/**
 * Incoming-SMS spam filter. Pure and dependency-free (no config, DB or network)
 * so it can be unit-tested on its own. messages.php calls sms_spam_reason()
 * before posting an inbound text to Discord and drops anything that matches.
 *
 * Matching is deliberately narrow: known sender numbers and known fundraiser /
 * mass-texting link hosts, never broad keywords, so a genuine student text is
 * not dropped by accident. Extend the lists without a code change through the
 * SMS_SPAM_BLOCK_NUMBERS and SMS_SPAM_BLOCK_PATTERNS config constants (comma or
 * newline separated); a pattern may be a multi-word phrase.
 */

/**
 * Full-number comparison form (digits only, country code kept). A bare 10-digit
 * value is assumed US/Canada (+1) so a config entry like "312-487-4143" matches
 * the E.164 "+13124874143" Twilio sends. International numbers keep their country
 * code, so the same trailing digits under a different code are NOT a match.
 */
function sms_spam_normalize_number($n) {
    $digits = preg_replace('/[^0-9]/', '', (string) $n);
    if (strlen($digits) === 10) {
        $digits = '1' . $digits;
    }
    return $digits;
}

/** Split a comma/newline separated constant into trimmed, non-empty tokens. */
function sms_spam_split_list($value) {
    $out = array();
    foreach (preg_split('/[\r\n,]+/', (string) $value) as $token) {
        $token = trim($token);
        if ($token !== '') {
            $out[] = $token;
        }
    }
    return $out;
}

/**
 * Return a short reason string when an inbound SMS looks like spam, or null
 * when it should be delivered to Discord as normal.
 */
function sms_spam_reason($from, $body) {
    $from_n = sms_spam_normalize_number($from);
    $text   = strtolower((string) $body);

    // Full sender numbers to drop outright. A confirmed spam number carries no
    // false-positive risk. Stored in E.164 digit form (country code included).
    $numbers = array(
        '13124874143', // Snap! Raise fundraiser blast (2026-11), +1 312 487 4143
    );

    // Link hosts / brand strings that mark a message as a fundraiser or promo
    // blast. Seeded with the Snap! Raise family; 'snap.app' also covers the
    // 'sms.snap.app' short links. Matched as a lowercase substring of the body.
    $patterns = array(
        'snap.app',
        'snapraise',
        'snap.raise',
    );

    if (defined('SMS_SPAM_BLOCK_NUMBERS')) {
        foreach (sms_spam_split_list(SMS_SPAM_BLOCK_NUMBERS) as $extra) {
            $numbers[] = $extra;
        }
    }
    if (defined('SMS_SPAM_BLOCK_PATTERNS')) {
        foreach (sms_spam_split_list(SMS_SPAM_BLOCK_PATTERNS) as $extra) {
            $patterns[] = strtolower($extra);
        }
    }
    // Compare every entry in the same normalized form as the sender.
    $numbers = array_map('sms_spam_normalize_number', $numbers);

    if ($from_n !== '' && in_array($from_n, $numbers, true)) {
        return 'blocked sender ' . $from_n;
    }
    foreach ($patterns as $needle) {
        if ($needle !== '' && strpos($text, $needle) !== false) {
            return 'matched pattern "' . $needle . '"';
        }
    }
    return null;
}
