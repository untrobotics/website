<?php

class DiscordBot {
	
	private function send_api_request($URI, $method = 'GET', $data = null) {
		$ch = curl_init();
		
		$headers = array();
		$headers[] = 'Authorization: Bot ' . static::AUTH_TOKEN;
		$headers[] = 'Content-Type: application/json';

		curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api' . $URI);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		$payload = "";
		if ($data) {
			$payload = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		}
		$headers[] = 'Content-Length: ' . strlen($payload);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new DiscordBotException("Error occurred when making API request: " . curl_error($ch));
		}
		
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);
		
		$response = new stdClass();
		$response->result = $result;
		$response->status_code = $status_code;
		
		return $response;
	}
	
	public static function send_message($message, $channel_id) {
		$data = new stdClass(); // can't be bothered right now to make a class
		$data->content = $message;
		
		return static::send_api_request("/channels/{$channel_id}/messages", 'POST', $data);
	}
	
	public static function add_user_role($guild_id, $user_id, $role_id) {
		return static::send_api_request("/guilds/{$guild_id}/members/{$user_id}/roles/{$role_id}", 'PUT');
	}
	
	public static function remove_user_role($guild_id, $user_id, $role_id) {
		return static::send_api_request("/guilds/{$guild_id}/members/{$user_id}/roles/{$role_id}", 'DELETE');
	}
	
	public static function type($channel_id) {
		return static::send_api_request("/channels/{$channel_id}/typing", 'POST');
	}

	public static function get_all_users($guild_id) {
	    return static::send_api_request("/guilds/{$guild_id}/members?limit=1000");
    }
}
								
class DiscordBotException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
	}
}