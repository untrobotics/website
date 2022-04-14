<?php
require_once(__DIR__ . '/../../../template/config.php');
require_once(__DIR__ . '/../bot.php');

class AdminBot extends DiscordBot {
	protected const AUTH_TOKEN = DISCORD_ADMIN_BOT_TOKEN;
	
	public static function send_message($message, $channel_id = DISCORD_ADMIN_CHANNEL_ID, $attachments = null) {
		return parent::send_message($message, $channel_id, $attachments);
	}
	
	public static function add_user_role($user_id, $role_id = DISCORD_GOOD_STANDING_ROLE_ID, $guild_id = DISCORD_GUILD_ID) {
		return parent::add_user_role($guild_id, $user_id, $role_id);
	}

	public static function remove_user_role($user_id, $role_id = DISCORD_GOOD_STANDING_ROLE_ID, $guild_id = DISCORD_GUILD_ID) {
		return parent::remove_user_role($guild_id, $user_id, $role_id);
	}
	
	public static function type($channel_id = DISCORD_ADMIN_CHANNEL_ID) {
		return parent::type($channel_id);
	}

	public static function get_all_users($guild_id = DISCORD_GUILD_ID) {
	    return parent::get_all_users($guild_id);
    }

    public static function get_server_details($guild_id = DISCORD_GUILD_ID) {
        return parent::send_api_request("/guilds/{$guild_id}?with_counts=true");
    }

    public static function get_member_count($guild_id = DISCORD_GUILD_ID) {
        $server_details = static::get_server_details($guild_id);
        return $server_details->result->approximate_member_count;
    }

    public static function create_dm($recipient_id) {
        $data = new stdClass(); // can't be bothered right now to make a class
        $data->recipient_id = $recipient_id;

        $dm = parent::send_api_request("/users/@me/channels", "POST", "application/json", $data);
        return $dm->result->id;
    }
}