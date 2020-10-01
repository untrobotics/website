<?php
require_once(__DIR__ . '/../../../template/config.php');
require_once(__DIR__ . '/../bot.php');

class AdminBot extends DiscordBot {
	protected const AUTH_TOKEN = DISCORD_ADMIN_BOT_TOKEN;
	
	public static function send_message($message, $channel_id = DISCORD_ADMIN_CHANNEL_ID) {
		return parent::send_message($message, $channel_id);
	}
	
	public static function add_user_role($user_id, $role_id = DISCORD_GOOD_STANDING_ROLE_ID, $guild_id = DISCORD_GUILD_ID) {
		return parent::add_user_role($guild_id, $user_id, $role_id);
	}
	
	// TODO: implement this
	public static function remove_user_role($user_id, $role_id = DISCORD_GOOD_STANDING_ROLE_ID, $guild_id = DISCORD_GUILD_ID) {
		return parent::remove_user_role($guild_id, $user_id, $role_id);
	}
	
	public static function type($channel_id = DISCORD_ADMIN_CHANNEL_ID) {
		return parent::type($channel_id);
	}
}