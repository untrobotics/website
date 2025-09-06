<?php
require_once(__DIR__ . "/../bots/admin.php");
require_once(__DIR__ . "/../../../template/config.php");

$commands = json_decode(file_get_contents(__DIR__ . "/commands.json"), false);
$errs = [];
foreach ($commands as $command) {
    try {
        AdminBot::add_application_command($command, DISCORD_APP_CLIENT_ID, DISCORD_GUILD_ID);
    } catch (DiscordBotException $e) {
        $errs[] = $command->name . ": " . $e->getMessage();
    }
}
if(count($errs) > 0) {
    error_log("Couldn't add/update the following commands to the Discord bot:\n" . implode("\n", $errs));
}