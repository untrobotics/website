<?php
require_once(__DIR__ . '/../../template/functions/mime2ext.php');

class DiscordBot {

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

	public static function send_message($message, $channel_id, $attachments = null) {
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

    // utils
    public static function hasHitRateLimit($result) {
        return $result->status_code == 429;
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