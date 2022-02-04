<?php

if (php_sapi_name() != "cli") {
    die();
}

require_once(__DIR__ . '/../../../template/config.php');
require_once(__DIR__ . '/admin.php');

$message = $argv[1];
$prev = $argv[2];
$current = $argv[3];
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
    "force-download.php"
);

$offending_patterns = array(
    "@^AH01797@"
);

foreach ($not_found_paths_to_ignore as $path) {
    $offending_patterns[] = "@^script '/var/www/untrobotics/{$path}' not found or unable to stat@";
}

if ($did_match) {
    foreach ($matches[0] as $k => $m) {
        $timestamp = $matches[1][$k];
        $error_type = $matches[2][$k];
        $process_pid = $matches[3][$k];
        $request_info = $matches[4][$k];
        $error_message = $matches[5][$k];

        foreach ($offending_patterns as $pattern) {
            if (preg_match($pattern, $error_message)) {
                continue 2;
            }
        }

        AdminBot::send_message(
            "```accesslog\n({$prev} => {$current})\n[{$timestamp}]\n[{$error_type}]\n[{$process_pid}]\n[{$request_info}]\n\n{$error_message}```", $discord_channel
        );
    }
} else {
    var_dump(AdminBot::send_message("```({$prev} => {$current})\n[ERROR LOG MESSAGE PARSE FAILED]\n{$message}```", $discord_channel));
}
