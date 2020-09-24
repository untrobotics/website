<?php

class DiscordBot {
	//private $auth_token;
	
	private function send_api_request($URI, $method = 'GET', $data) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api' . $URI);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$headers = array();
		$headers[] = 'Authorization: Bot ' . static::AUTH_TOKEN;
		$headers[] = 'Content-Type: application/json';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			//echo 'Error:' . curl_error($ch);
			throw new DiscordBotException();
		}
		curl_close($ch);
		
		return json_decode($result);
	}
	
	public static function send_message($message, $channel_id) {
		$data = new stdClass(); // can't be bothered right now to make a class
		$data->content = $message;
		
		return static::send_api_request("/channels/{$channel_id}/messages", 'POST', $data);
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