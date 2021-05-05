<?php
// this page must be the result of a Discord Oauth2 request with this set as the REDIRECT_URI

require('../template/top.php'); // see https://github.com/sebastian-king/Sebs-Website-Framework
require(BASE . '/api/discord/bots/admin.php');

function get_discord_access_token($code, $scope) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, DISCORD_APP_API_URL . '/oauth2/token');
	curl_setopt($ch, CURLOPT_POST, 1);

	$post = array();
	$post['client_id'] = DISCORD_APP_CLIENT_ID;
	$post['client_secret'] = DISCORD_APP_CLIENT_SECRET;
	$post['grant_type'] = 'authorization_code';
	$post['code'] = $code;
	$post['redirect_uri'] = DISCORD_APP_REDIRECT_URI;
	$post['scope'] = $scope;

	$headers = array();
	$headers[] = 'Content-Type: application/x-www-form-urlencoded';

	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($ch);
	curl_close($ch);

	$data = json_decode($result);

	$access_token = $data->access_token;
	//var_dump($data);
	return $access_token;
}

function discord_request($uri, $access_token, $access_token_type = "Bearer", $parameters = '', $custom_protocol = false) {
	$ch = curl_init();

	$headers = array();
	if ($access_token_type == 'Bot') {
		$headers[] = "Authorization: {$access_token_type} " . DISCORD_ADMIN_BOT_TOKEN;
	} else {
		$headers[] = "Authorization: {$access_token_type} {$access_token}";
	}
	curl_setopt($ch, CURLOPT_URL, DISCORD_APP_API_URL . $uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($custom_protocol) {
		switch ($custom_protocol) {
			case 'PUT':
				$headers[] = 'Content-Type: application/json';
				$query = json_encode($parameters);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
				$headers[] = "Content-Length: " . strlen($query);
				break;
			default: // GET
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $custom_protocol);
			$headers[] = "Content-Length: 0";
		}
	}
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$returned_headers = array(); // this function is called by curl for each header received
	curl_setopt($ch, CURLOPT_HEADERFUNCTION,
	  function($curl, $header) use (&$returned_headers) {
		$len = strlen($header);
		$header = explode(':', $header, 2);
		if (count($header) < 2) // ignore invalid headers
		  return $len;

		$name = strtolower(trim($header[0]));
		if (!array_key_exists($name, $returned_headers)) {
		  $returned_headers[$name] = [trim($header[1])];
		} else {
		  $returned_headers[$name][] = trim($header[1]);
		}

		return $len;
	  }
	);

	$result = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	$data = json_decode($result);
	return array($data, $returned_headers, $httpcode);
}

function get_user_information($access_token) {
	$user = discord_request('/users/@me', $access_token);
	$user_headers = $user[1];
	$user = $user[0];
	return $user;
}

function add_user_to_server($user, $access_token, $name) {
	//$join_request = discord_request('/guilds/' . DISCORD_GUILD_ID . '/members/' . $user->id, $access_token, 'Bot', array('access_token' => $access_token, 'roles' => array(DISCORD_APP_ROLE_ID)), 'PUT');
	$join_request = discord_request('/guilds/' . DISCORD_GUILD_ID . '/members/' . $user->id, $access_token, 'Bot', array('access_token' => $access_token, 'nick' => 'smells'), 'PUT');
	$join_headers = $join_request[1];
	$join_httpcode = $join_request[2];
	$join = $join_request[0];
	
	if ($join_httpcode == 201 || $join_httpcode == 204) { // 204 means the user was already in the server
		return true;
	}
	return false;
}

function get_user_roles() {
	$roles = discord_request('/guilds/' . DISCORD_GUILD_ID . '/roles', $access_token, 'Bot');
	return $roles;
}

function assign_user_good_standing($user_discord_id) {
	return AdminBot::add_user_role($user_discord_id);
}

head('Joined Discord', true, true);

// user must be authenticated to reach this point

?>
<style>
strong.no-wrap {
	white-space: nowrap;
}
.scheme-buttons a {
	padding: 20px 10px;
	display: inline-block;
}
.scheme-buttons a img {
	width: 190px;
	background-color: white;
	border-radius: 8px;
	border: 3px solid black;
}
.scheme-buttons a img.white {
	display: none;
	margin: 0 !important;
}
.scheme-buttons a:hover img {
	background-color: black;
}
.scheme-buttons a:hover img.black {
	display: none;

}
.scheme-buttons a:hover img.white {
	display: inline-block;
}
</style>
<main class="page-content">
        <section class="section-50 section-md-75 section-lg-100">
                <div class="shell text-sm-left">
                        <div class="range text-center">
                                <div class="col-lg-6 col-lg-offset-3">
									<?php
									try {
										$code = null;
										if (isset($_GET['code'])) {
											$code = $_GET['code'];
										} else {
											// Alert!
											throw new Exception("No authorization code provided.");
										}

										$discord_access_token = get_discord_access_token($code, "identify guilds.join");
										$user = get_user_information($discord_access_token);


										$q = $db->query('UPDATE users SET discord_id = "' . $db->real_escape_string($user->id) . '" WHERE id = "' . $db->real_escape_string($userinfo['id']) . '"');
										if (!$q || $db->affected_rows !== 1) {
											// Alert!
											throw new Exception("Unable to set the user's Discord ID: " . $db->affected_rows);
										}

										$added_to_server = add_user_to_server($user, $discord_access_token, $userinfo['name']);

										if (!$added_to_server) {
											// Alert!
											throw new Exception("Failed to add user to server.");
										}

										$is_user_in_good_standing = $untrobotics->is_user_in_good_standing($userinfo);
										if ($is_user_in_good_standing) {
											$assigned = assign_user_good_standing($user->id);
											if ($assigned->status_code != 204) {
											    error_log("AUTHDIS", var_export($assigned, true));
												throw new Exception("Failed to give user the correct role: " . $assigned->status_code . " ($code)");
											}
										} else {
											// Alert!
											throw new Exception("Current user is not in good standing.");
										}
										?>
										<h1>You're good to go</h1>
										<h5 class="offset-top-50"><strong><?php echo $user->username; ?>#<?php echo $user->discriminator; ?></strong> has been given the <em>Good Standing</em> role.</h5>

										<div class="scheme-buttons offset-top-50">
											<a href="market://details?id=com.discord">
												<img class="black" src="/images/buttons/discord-forward-android-white.png"/>
												<img class="white" src="/images/buttons/discord-forward-android.png"/>
											</a>
											<a href="https://discordapp.com/channels/<?php echo DISCORD_GUILD_ID; ?>/<?php echo DISCORD_GENERAL_CHANNEL_ID; ?>">
												<img class="black"  src="/images/buttons/discord-forward-browser-white.png"/>
												<img class="white" src="/images/buttons/discord-forward-browser.png"/>
											</a>
											<a href="com.hammerandchisel.discord://">
												<img class="black"  src="/images/buttons/discord-forward-apple-white.png"/>
												<img class="white" src="/images/buttons/discord-forward-apple.png"/>
											</a>
										</div>
										<?php
									} catch (Exception $ex) {

									    $username = "N/A";
                                        $discriminator = "N/A";

									    if (@isset($user)) {
									        if (property_exists($user, "username")) {
									            $username = $user->username;
                                            }
                                            if (property_exists($user, "discriminator")) {
                                                $discriminator = $user->discriminator;
                                            }
                                        }

										AdminBot::send_message("[AUTHDIS] Failed to assign '{$userinfo['name']}' [{$username}#{$discriminator}] (http://untro.bo/admin/check-good-standing?u={$userinfo['id']}) to the Good Standing role.\n{$ex}");
										?>
										<div class="alert alert-danger">
											<h2 style="color: inherit;">Error!</h2>
											Unfortunately an error occurred while attemping to assign you the correct role. Please contact us for support at <a class="text-danger" href="mailto:<?php echo EMAIL_SUPPORT; ?>"><?php echo EMAIL_SUPPORT; ?></a>.
										</div>
										<?php
									}
									?>
                                </div>
                        </div>
                </div>
        </section>
</main>
<?php
footer();