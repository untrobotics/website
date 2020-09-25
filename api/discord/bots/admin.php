<?php
require_once(__DIR__ . '/../../../template/config.php');
require_once(__DIR__ . '/../bot.php');

class AdminBot extends DiscordBot {
	protected const AUTH_TOKEN = DISCORD_ADMIN_BOT_TOKEN;
	
	public static function send_message($message, $channel_id = DISCORD_ADMIN_CHANNEL_ID) {
		return parent::send_message($message, $channel_id);
	}
}