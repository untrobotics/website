<?php
require_once(__DIR__ . "/../bots/admin.php");
require_once(__DIR__ . "/../../../template/config.php");

// commands are stored in a JSON format. See https://discord.com/developers/docs/interactions/application-commands#application-command-object for object structure
$commands = json_decode(file_get_contents(__DIR__ . "/commands.json"), false);
$errs = [];
// have to register each command one-by-one. You can only bulk update commands, not register (I think)
foreach ($commands as $command) {
    // try to add the app command and log the error to the error array if it fails
    try {
        AdminBot::add_application_command($command, DISCORD_APP_CLIENT_ID, DISCORD_GUILD_ID);
    } catch (DiscordBotException $e) {
        $errs[] = $command->name . ": " . $e->getMessage();
    }
}

// log errors
if(count($errs) > 0) {
    error_log("Couldn't add/update the following commands to the Discord bot:\n" . implode("\n", $errs));
}