<?php

if (php_sapi_name() == "cli") {
    die();
}

require_once(__DIR__ . '/../../../template/config.php');
require_once(__DIR__ . '/admin.php');

$message = $argv[1];

$did_match = preg_match("@^\[(.+)\] \[(.+)\] \[(.+)\] \[(.+)\] (.+)$@ms", $message, $m);

if ($did_match) {
    $timestamp = $m[1];
    $error_type = $m[2];
    $process_pid = $m[3];
    $request_info = $m[4];
    $error_message = $m[5];

    AdminBot::send_message(
        "```accesslog\n[{$timestamp}]\n[{$error_type}]\n[{$process_pid}]\n[{$request_info}]\n\n{$error_message}```",
        759511118976122940);
} else {
    AdminBot::send_message("```[ERROR LOG MESSAGE PARSE FAILED]\n{$message}```", 759511118976122940);
}