<?php

if (php_sapi_name() != "cli") {
    die();
}

require_once(__DIR__ . '/../../../template/config.php');
require_once(__DIR__ . '/admin.php');

$message = $argv[1];
$prev = $argv[2];
$current = $argv[3];

// URW-211: defence-in-depth. The log-forwarder already truncates the line
// before exec (the E2BIG limit is enforced at the argv boundary, not here), but
// keep a cap so a pathological line can't produce an oversized Discord payload.
if (strlen($message) > 6000) {
    $message = substr($message, 0, 6000) . ' …[truncated]';
}

$len = strlen(trim($message));

if ($len == 0) {
    die();
}

$did_match = preg_match_all("@^\[(.+?)\] \[(.+?)\] \[(.+?)\] \[(.+?)\] (.+?)$@ms", $message, $matches);

$discord_channel = DISCORD_DEV_WEB_LOGS_CHANNEL_ID;
if (ENVIRONMENT == Environment::PRODUCTION) {
    $discord_channel = DISCORD_WEB_LOGS_CHANNEL_ID;
}

$not_found_paths_to_ignore = array(
    "wp-login.php",
    "xmlrpc.php",
    "wp-config.php",
    "force-download.php",
    "maintenance.php",
    "insom.php",
    "demit.php",
    "up.php",
    "fuck.php",
    "modules.php",
    "286118814.php",
    "vertigo.php",
    "Foto2018.php",
    "Foto02018.php",
    "Drupal2019.php",
    "logo2019.php",
    "drupal.php",
    "zeXXX.php",
    "ramz.php",
    "cia.php",
    "pilat.php",
    "accesson.php",
    "renata.php",
    "authorize_old.php",
    "ccaef.php",
    "sh3llx",
    "xlet",
    "jindex",
    "admin" // do we need a special handler for admin?
);

$offending_patterns = array(
    "@^AH01797@",   // client denied by server config (existing)
    "@^AH01630@",   // client denied — probes for /server-status, /.htpasswd, etc.
    "@^AH01276@",   // "Cannot serve directory ... no DirectoryIndex" — this is just
                    //   Options -Indexes doing its job when someone hits a dir.
    "@^AH00126@",   // "Invalid URI in request" — malformed / path-traversal attack
                    //   probes (e.g. .../proc/self/environ) that Apache already rejects.
);

foreach ($not_found_paths_to_ignore as $path) {
    $offending_patterns[] = "@^script '/var/www/untrobotics/{$path}' not found or unable to stat@i";
}

if ($did_match) {
    foreach ($matches[0] as $k => $m) {
        $timestamp = $matches[1][$k];
        $error_type = $matches[2][$k];
        $process_pid = $matches[3][$k];
        $request_info = $matches[4][$k];
        $error_message = $matches[5][$k];

        // Skip low-value notice-level entries (Apache/PHP lifecycle + info) — these
        // are not actionable and were spamming #web-logs on every deploy/restart.
        if (stripos($error_type, 'notice') !== false) {
            continue;
        }

        foreach ($offending_patterns as $pattern) {
            if (preg_match($pattern, $error_message)) {
                continue 2;
            }
        }

        // URW-14: post the log entry as a rich embed instead of a raw code block —
        // colour-coded by severity with structured fields, far more readable in
        // #web-logs than a wall of monospace.
        $sev = strtolower($error_type);
        $color = 0x95A5A6; // grey (default / info-ish)
        if (preg_match('/(emerg|alert|crit|error|fatal)/', $sev)) {
            $color = 0xE74C3C; // red
        } elseif (strpos($sev, 'warn') !== false) {
            $color = 0xE67E22; // orange
        } elseif (preg_match('/(info|debug)/', $sev)) {
            $color = 0x3498DB; // blue
        }

        $embed = new stdClass();
        $embed->color = $color;
        $embed->title = mb_substr($error_type, 0, 256);
        // Wrap the message in a code block for monospace; embed descriptions cap at 4096.
        $embed->description = "```\n" . mb_substr($error_message, 0, 3900) . "\n```";
        $embed->timestamp = date('c'); // when it was forwarded (Apache time is in a field)
        $embed->fields = array();
        if (trim($request_info) !== '') {
            $rf = new stdClass();
            $rf->name = 'Request';
            $rf->value = mb_substr($request_info, 0, 1024);
            $rf->inline = false;
            $embed->fields[] = $rf;
        }
        $pf = new stdClass();
        $pf->name = 'PID';
        $pf->value = (string) ($process_pid !== '' ? $process_pid : '—');
        $pf->inline = true;
        $embed->fields[] = $pf;
        $tf = new stdClass();
        $tf->name = 'Apache time';
        $tf->value = mb_substr($timestamp, 0, 1024);
        $tf->inline = true;
        $embed->fields[] = $tf;
        $footer = new stdClass();
        $footer->text = "log offset {$prev} → {$current}";
        $embed->footer = $footer;

        $payload = new stdClass();
        $payload->embeds = array($embed);
        AdminBot::send_message($payload, $discord_channel);
    }
}
// Lines that don't match the standard Apache error format (module lifecycle
// notices, multi-line-entry fragments, etc.) are dropped silently — posting an
// "[ERROR LOG MESSAGE PARSE FAILED]" for each just spammed #web-logs.
