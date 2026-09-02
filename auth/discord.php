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


										// URW-10: if this user previously linked a *different* Discord account,
										// strip the Good Standing role from that old account before repointing the
										// record — otherwise the abandoned account keeps the role forever.
										$prev = $db->query('SELECT discord_id FROM users WHERE id = "' . $db->real_escape_string($userinfo['id']) . '"');
										$prev_discord_id = ($prev && ($prev_row = $prev->fetch_assoc())) ? $prev_row['discord_id'] : null;
										if (!empty($prev_discord_id) && $prev_discord_id !== $user->id) {
											$removed = AdminBot::remove_user_role($prev_discord_id);
											// 204 = removed, 404 = member/role already gone; both are fine. Anything
											// else is non-fatal here — log it but don't block the re-link.
											if (!isset($removed->status_code) || ($removed->status_code != 204 && $removed->status_code != 404)) {
												AdminBot::send_message("[AUTHDIS] Could not remove Good Standing from previous Discord account {$prev_discord_id} for '{$userinfo['name']}' (status " . ($removed->status_code ?? 'n/a') . ").");
											}
										}

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
										}
										// Not being in good standing is the normal case for anyone who
										// hasn't paid dues: they've still linked their account and joined
										// the server. It is NOT an error, so don't throw (that would show
										// the user an error page and alert officers with a stack trace).
										// Show a friendly message either way.
										?>
										<?php if ($is_user_in_good_standing) { ?>
										<h1>You're good to go</h1>
										<h5 class="offset-top-50"><strong><?php echo htmlspecialchars($user->username); ?></strong>, you've joined the UNT Robotics Discord and you have the <em>Good Standing</em> role. Welcome!</h5>
										<?php } else { ?>
										<h1>You're in!</h1>
										<h5 class="offset-top-50">Welcome, <strong><?php echo htmlspecialchars($user->username); ?></strong>. You've joined the UNT Robotics Discord. Once you've <a href="/dues">paid your dues</a> for the semester you'll also get the <em>Good Standing</em> role.</h5>
										<?php } ?>

										<style>
											.discord-ctas { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
											.discord-cta { display:inline-flex; align-items:center; justify-content:center; gap:11px; height:54px; padding:0 30px; border-radius:10px; font-size:16px; font-weight:700; text-decoration:none; transition:background .15s, color .15s, transform .05s; }
											.discord-cta svg { width:24px; height:24px; fill:currentColor; }
											.discord-cta.primary { background:#5865F2; color:#fff; }
											.discord-cta.primary:hover { background:#4752c4; color:#fff; }
											.discord-cta.ghost { color:#5865F2; border:2px solid #5865F2; background:transparent; }
											.discord-cta.ghost:hover { background:#5865F2; color:#fff; }
											.discord-cta:active { transform:translateY(1px); }
										</style>
										<div class="discord-ctas offset-top-50">
											<a class="discord-cta primary" href="https://discord.com/channels/<?php echo DISCORD_GUILD_ID; ?>/<?php echo DISCORD_GENERAL_CHANNEL_ID; ?>">
												<svg viewBox="0 0 640 512" aria-hidden="true"><path d="M524.5 69.8a1.5 1.5 0 0 0-.8-.7A485.1 485.1 0 0 0 404.1 32a1.8 1.8 0 0 0-1.9.9 337.5 337.5 0 0 0-14.9 30.6 447.8 447.8 0 0 0-134.4 0 309.5 309.5 0 0 0-15.1-30.6 1.9 1.9 0 0 0-1.9-.9A483.7 483.7 0 0 0 116.1 69.1a1.7 1.7 0 0 0-.8.7C39.1 183.7 18.2 294.7 28.4 404.4a2 2 0 0 0 .8 1.4A487.7 487.7 0 0 0 176 479.9a1.9 1.9 0 0 0 2.1-.7A348.2 348.2 0 0 0 208.1 430.4a1.9 1.9 0 0 0-1-2.6 321.2 321.2 0 0 1-45.9-21.9 1.9 1.9 0 0 1-.2-3.1c3.1-2.3 6.2-4.7 9.1-7.1a1.8 1.8 0 0 1 1.9-.3c96.2 43.9 200.4 43.9 295.5 0a1.8 1.8 0 0 1 1.9.2c2.9 2.4 6 4.9 9.1 7.2a1.9 1.9 0 0 1-.2 3.1 301.4 301.4 0 0 1-45.9 21.8 1.9 1.9 0 0 0-1 2.6 391.1 391.1 0 0 0 30 48.8 1.9 1.9 0 0 0 2.1.7A486 486 0 0 0 610.7 405.7a1.9 1.9 0 0 0 .8-1.4C623.7 277.6 590.9 167.5 524.5 69.8zM222.5 337.6c-29 0-52.8-26.6-52.8-59.2S193 219.1 222.5 219.1c29.7 0 53.3 26.8 52.8 59.2 0 32.6-23.4 59.2-52.8 59.2zm195.4 0c-29 0-52.8-26.6-52.8-59.2S388.4 219.1 417.9 219.1c29.7 0 53.3 26.8 52.8 59.2 0 32.6-23.1 59.2-52.8 59.2z"/></svg>
												Open Discord
											</a>
											<a class="discord-cta ghost" href="https://discord.com/download">Get the app</a>
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