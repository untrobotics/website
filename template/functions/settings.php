<?php
/**
 * Runtime settings backed by the app_settings table, so operational values can
 * be changed from the admin panel without a redeploy. Reads are cached per
 * request (shared between get + set so a write is visible immediately). Falls
 * back to a provided default (or the matching config constant) when the row or
 * table is missing, so the site still works pre-migration.
 */

/** Shared per-request cache (by-reference so get + set see the same store). */
function &_settings_cache() {
    static $cache = array();
    return $cache;
}

function setting_get($key, $default = null) {
    global $db;
    $cache =& _settings_cache();
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $value = $default;
    if (isset($db) && $db) {
        // Suppress errors: the table may not exist yet on a fresh DB (pre-migration).
        $r = @$db->query('SELECT `value` FROM app_settings WHERE `key` = "' . $db->real_escape_string($key) . '" LIMIT 1');
        if ($r && $r->num_rows) {
            $value = $r->fetch_assoc()['value'];
        }
    }
    $cache[$key] = $value;
    return $value;
}

function setting_set($key, $value) {
    global $db;
    $k = $db->real_escape_string($key);
    $v = $db->real_escape_string((string) $value);
    $ok = $db->query('INSERT INTO app_settings (`key`, `value`) VALUES ("' . $k . '", "' . $v . '") ON DUPLICATE KEY UPDATE `value` = "' . $v . '"');
    if ($ok) {
        $cache =& _settings_cache();
        $cache[$key] = (string) $value;
    }
    return (bool) $ok;
}

/** The current Botathon season (admin-settable; defaults to the config value). */
function botathon_season() {
    $fallback = defined('BOTATHON_SEASON') ? BOTATHON_SEASON : 1;
    return (int) setting_get('botathon_season', $fallback);
}
