<?php
// sample incrontab entry:
// /var/log/apache2/untrobotics/error.log IN_MODIFY /www/api/discord/bots/admin-cli.php $#
if (php_sapi_name() != "cli") {
    die();
}

// fetch previously read log length and update with current length
try{
    require_once(__DIR__ . '/../../../template/top.php');
    global $db;
    // fetch last line number read from log file
    $q = $db->query('SELECT prev_len FROM error_log_index');
    $prev = $q === false ? 0 : $q->fetch_row()[0];

    // fetch all lines of log file
    $lines = file($argv[1]);
    if($lines === false) {
        $current = 0;
        $message = "Error log file '$argv[1]' not found.";
    } else {
        // update last line number read and set message
        $current = count($lines);
        $message = trim(implode('', array_slice($lines, $prev)));
        if($current !== 0) {
            $q = $db->query("UPDATE error_log_index SET prev_len = $current");
            if (strlen($message) === 0) {
                die();
            } else if ($q === false) {
                $message = "Could not update error log file to offset {$prev}";
            }
        }
        else {
            $message = 'Log file was empty or could not be read.';
        }
    }
} catch(mysqli_sql_exception $e){
    $message = "Error occurred while fetching/updating stored error log length in the db:\n{$e->getMessage()}";
    $prev = 0;
    $current = 0;
} catch(Exception $e) {
    $message = "Error occurred while attempting to retrieve error logs:\n{$e->getMessage()}";
    $prev = 0;
    $current = 0;
}

require_once(__DIR__ . '/../../../template/config.php');
require_once(__DIR__ . '/admin.php');

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
    "@^AH01797@"
);

foreach ($not_found_paths_to_ignore as $path) {
    $offending_patterns[] = "@^script '/var/www/untrobotics/{$path}' not found or unable to stat@i";
}

if ($did_match) {
    try {
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

            // chunk error message into 2000 char chunks for discord limits
            $discord_message = "({$prev} => {$current})\n[{$timestamp}]\n[{$error_type}]\n[{$process_pid}]\n[{$request_info}]\n\n{$error_message}";
            for($i = 0; $i < strlen($discord_message); $i+= 1984) { // code block formatting is 16 chars
                AdminBot::send_message(
                    "```accesslog\n" . substr($discord_message,$i,1984) . "```", $discord_channel
                );
            }
        }
    } catch (Exception $e) {
        var_dump(AdminBot::send_message("```({$prev} => {$current})\n[ERROR LOG MESSAGE PARSE FAILED]\n{$message}```", $discord_channel));
    }
} else {
    var_dump(AdminBot::send_message("```({$prev} => {$current})\n[ERROR LOG MESSAGE PARSE FAILED]\n{$message}```", $discord_channel));
}
