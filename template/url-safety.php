<?php
/**
 * URL-safety helpers (URW-52), extracted from template/top.php so they can be
 * unit-tested in isolation — top.php opens a DB connection and starts a session
 * at include time, which a unit test must not do. top.php require_once's this
 * file; tests require it directly. The function_exists guards keep a double
 * include harmless.
 */

if (!function_exists('safe_returnto')) {
    /**
     * Validate a `returnto` redirect target. Only a same-origin, root-relative
     * path is allowed — a single leading slash followed by a non-slash (blocks
     * "//evil.com" and "/\evil.com" protocol-relative tricks), no scheme, no
     * backslash, no CR/LF (header injection), and never a path under /auth (which
     * would bounce the user straight back into the login flow). Returns the safe
     * path, or null if missing/invalid. Input is expected already URL-decoded (as
     * PHP hands it over via $_GET).
     */
    function safe_returnto($raw) {
        if (!is_string($raw) || $raw === '') return null;
        if (preg_match('@[\x00-\x1f\x7f\\\\]@', $raw)) return null; // control chars / backslash
        if (!preg_match('@^/[^/]@', $raw)) return null;             // must be "/<non-slash>"
        if (preg_match('@^/auth(/|$)@i', $raw)) return null;        // don't loop back into auth
        return $raw;
    }
}

if (!function_exists('returnto_qs')) {
    /**
     * Build a "?returnto=<url-encoded>" suffix for a login/join link. Defaults to
     * the current request URI; returns "" when the target isn't a valid returnto,
     * so the link degrades to a plain /auth/login.
     */
    function returnto_qs($target = null) {
        if ($target === null) $target = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        return safe_returnto($target) === null ? '' : '?returnto=' . urlencode($target);
    }
}
