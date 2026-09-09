<?php
require_once(__DIR__ . '/../../template/functions/mime2ext.php');

/**
 * Low-level Discord REST API client. Abstract in practice: it authenticates with
 * a bot token read from `static::AUTH_TOKEN`, so it's used via a subclass that
 * defines that constant (see AdminBot). Methods are static and return a response
 * object ({ result: decoded JSON, status_code: int }).
 */
class DiscordBot {

	/**
	 * Make an authenticated request to the Discord API.
	 *
	 * @param string      $URI          API path beginning with '/' (appended to https://discord.com/api).
	 * @param string      $method       HTTP method (GET, POST, PUT, DELETE, ...).
	 * @param string      $content_type "application/json" or "multipart/form-data" (for file uploads).
	 * @param mixed       $data         Request body; JSON-encoded, or sent as payload_json for multipart.
	 * @param array|null  $files        CURLFile entries to attach when multipart.
	 * @return stdClass    { result: decoded JSON response, status_code: int }.
	 * @throws DiscordBotException On a cURL transport error.
	 */
	protected static function send_api_request($URI, $method = 'GET', $content_type = "application/json", $data = null, $files = null) {
		$ch = curl_init();

		$headers = array();
		$headers[] = 'Authorization: Bot ' . static::AUTH_TOKEN;
		$headers[] = 'Content-Type: ' . $content_type; //multipart/form-data';

		curl_setopt($ch, CURLOPT_URL, 'https://discord.com/api' . $URI);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

		//var_dump("DEBUG", $URI, $method, $content_type, $data, $files);

		if ($data && $content_type == "multipart/form-data") {
			$payload = array(
			    'payload_json' => json_encode($data)
            );
			if ($files != null && count($files) > 0) {
			    $payload = array_merge($payload, $files);
            }

			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
		} else if ($data && $content_type == "application/json") {
		    $payload = json_encode($data);
            //$headers[] = 'Content-Length: ' . strlen($payload);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } else {
            $headers[] = 'Content-Length: 0';
        }

		//var_dump($headers);

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			throw new DiscordBotException("Error occurred when making API request: '" . curl_error($ch) . "'" . "(" . curl_errno($ch) . ")");
		}

		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);

		$response = new stdClass();
		$response->result = json_decode($result);
		$response->status_code = $status_code;

		return $response;
	}

	/**
	 * Post a message to a channel. No-op (logged only) outside production, since
	 * dev/staging share the prod bot token + channel ids and would otherwise spam
	 * the real server.
	 *
	 * @param string|object $message     Message text, or a prepared message object.
	 * @param string        $channel_id  Target channel id.
	 * @param array|null    $attachments Attachments; each is ['bin'|'path'|'url', 'type', ...].
	 * @return stdClass|null The API response, or null when suppressed in non-prod.
	 */
	public static function send_message($message, $channel_id, $attachments = null) {
		// Only post to Discord from production. Dev/staging share the same bot
		// token and channel ids through config, so without this guard dev web
		// actions (newsletter sign-ups, donations, dues, etc.) spam the real
		// server. Log what would have been sent instead of sending it.
		if (!defined('ENVIRONMENT') || ENVIRONMENT !== Environment::PRODUCTION) {
			error_log('[discord suppressed in non-prod] channel ' . $channel_id . ': ' . (is_string($message) ? $message : json_encode($message)));
			return null;
		}
	    if (is_string($message)) {
            $data = new stdClass(); // can't be bothered right now to make a class
            $data->content = $message;
        } else {
	        $data = $message;
        }

		// Add files to array
		$files = array();
        $tmp_files = [];    // needed if more than 1 attachment to prevent gc for deleting the temp files
		if ($attachments) {
			foreach ($attachments as $k => $attachment) {
				if(isset($attachment['bin']))	// Function caller passed the raw data to the arg
				{
					// Since we have the data, we need to create a tmpfile to store that data for the CURLFile
					$tmp_files[$k] = tmpfile();
					$path = stream_get_meta_data($tmp_files[$k])['uri'];
					file_put_contents($path,$attachment['bin']);
					$file_type = $attachment['type'];
					$file_mime = ext2mime($file_type);
				}
				else if(isset($attachment['path']))	// File exists on local machine
				{
					$path = $attachment['path'];
					$file_type = $attachment['type'];
					$file_mime = ext2mime($file_type);
				}
				else	// We assume the attachment is an online file that we need to download
				{	// sebastian only insane people put curly braces on the same line
					//$content = file_get_contents($attachment['url']);
                    $tmp_files[$k] = tmpfile();
                    $path = stream_get_meta_data($tmp_files[$k])['uri'];
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $attachment['url']);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$content = curl_exec($ch);

					//error_log($content);

					file_put_contents($path, $content);
					$file_mime = $attachment['type'];
					$file_type = mime2ext($file_mime);
					//error_log($attachment['url']);
					//error_log($path);
					//error_log($content);

					/*$files["attachment{$k}"] = new CURLFile($path, $attachment['type'], "attachment{$k}." . mime2ext($attachment['type']));*/
				}

				// CURLFile is a file with a bunch of delimiters in the binary so it can be sent as Form-data
				$files["attachment{$k}"] = new CURLFile($path, $file_mime, "attachments{$k}." . $file_type);
			}
		}
		//error_log(var_export($files, true));

		return static::send_api_request("/channels/{$channel_id}/messages", 'POST', 'multipart/form-data', $data, $files);
	}

	/**
	 * Add a role to a guild member.
	 *
	 * @param string $guild_id Guild (server) id.
	 * @param string $user_id  Member's Discord user id.
	 * @param string $role_id  Role id to add.
	 * @return stdClass The API response.
	 */
	public static function add_user_role($guild_id, $user_id, $role_id) {
		return static::send_api_request("/guilds/{$guild_id}/members/{$user_id}/roles/{$role_id}", 'PUT');
	}

	/**
	 * Remove a role from a guild member.
	 *
	 * @param string $guild_id Guild (server) id.
	 * @param string $user_id  Member's Discord user id.
	 * @param string $role_id  Role id to remove.
	 * @return stdClass The API response.
	 */
	public static function remove_user_role($guild_id, $user_id, $role_id) {
		return static::send_api_request("/guilds/{$guild_id}/members/{$user_id}/roles/{$role_id}", 'DELETE');
	}

	/**
	 * Trigger the "typing…" indicator in a channel.
	 *
	 * @param string $channel_id Channel id.
	 * @return stdClass The API response.
	 */
	public static function type($channel_id) {
		return static::send_api_request("/channels/{$channel_id}/typing", 'POST');
	}

	/**
	 * List up to 1000 members of a guild.
	 *
	 * @param string $guild_id Guild (server) id.
	 * @return stdClass The API response (result is the member array).
	 */
	public static function get_all_users($guild_id) {
	    return static::send_api_request("/guilds/{$guild_id}/members?limit=1000");
    }

    // utils

    /**
     * @param stdClass $result A response from send_api_request().
     * @return bool True if Discord returned HTTP 429 (rate limited).
     */
    public static function hasHitRateLimit($result) {
        return $result->status_code == 429;
    }
}

/**
 * Thrown by DiscordBot when a Discord API request fails at the transport level.
 */
class DiscordBotException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
	}
}