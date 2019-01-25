<?php
require('../template/top.php'); // see https://github.com/sebastian-king/Sebs-Website-Framework

function get_discord_access_token($code, $scope) {
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, DISCORD_APP_API_ENDPOINT . '/oauth2/token');
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
	return $access_token;
}

function discord_request($uri, $access_token, $access_token_type = "Bearer", $parameters = '', $custom_protocol = false) {
	$ch = curl_init();

	$headers = array();
	if ($access_token_type == 'Bot') {
		$headers[] = "Authorization: {$access_token_type} " . DISCORD_APP_BOT_TOKEN;
	} else {
		$headers[] = "Authorization: {$access_token_type} {$access_token}";
	}
	curl_setopt($ch, CURLOPT_URL, DISCORD_APP_API_ENDPOINT . $uri);
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

$access_token = get_discord_access_token($_GET['code'], 'identify guilds.join');
$user = discord_request('/users/@me', $access_token);
$user_headers = $user[1];
$user = $user[0];

//$roles = discord_request('/guilds/' . DISCORD_GUILD_ID . '/roles', $access_token, 'Bot');

$join_request = discord_request('/guilds/' . DISCORD_GUILD_ID . '/members/' . $user->id, $access_token, 'Bot', array('access_token' => $access_token, 'roles' => array(DISCORD_APP_ROLE_ID)), 'PUT');
$join_headers = $join_request[1];
$join_httpcode = $join_request[2];
$join = $join_request[0];

//var_dump($user, $join_headers);
//var_dump($join, $join_headers, $join_httpcode);

head('Joined Discord', true);

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
	background-color: black;
	border-radius: 8px;
	border: 3px solid black;
}
.scheme-buttons a img.white {
	display: none;
	margin: 0 !important;
}
.scheme-buttons a:hover img {
	background-color: white;
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
									if ($join_httpcode == 201 || $join_httpcode == 204) { // if $user fails, so will $join
										// httpcode 204 means user already added, however the role may not have been set therefore its best to still display the success message
									?>
									<h1>Your account has been added</h1>
									<h5 class="offset-top-50"><strong><?php echo $user->username; ?>#<?php echo $user->discriminator; ?></strong> has been granted access to <strong class="no-wrap">#<?php echo DISCORD_CHANNEL_NAME; ?></strong> on the <strong class="no-wrap"><?php echo DISCORD_GUILD_NAME; ?></strong> <?php echo DISCORD_SERVICE_NAME; ?> server</h5>

									<div class="scheme-buttons offset-top-50">
										<a href="market://details?id=com.discord">
											<img class="black" src="/images/buttons/discord-forward-android.png"/>
											<img  class="white" src="/images/buttons/discord-forward-android-white.png"/>
										</a>
										<a href="https://discordapp.com/channels/376974049043021825/507260076319440922">
											<img class="black"  src="/images/buttons/discord-forward-browser.png"/>
											<img class="white" src="/images/buttons/discord-forward-browser-white.png"/>
										</a>
										<a href="com.hammerandchisel.discord://">
											<img class="black"  src="/images/buttons/discord-forward-apple.png"/>
											<img  class="white" src="/images/buttons/discord-forward-apple-white.png"/>
										</a>
									</div>
                               		<?php
									} else {
										?>
										<div class="alert alert-danger">
											<h2>Error!</h2>
											Unfortunately an error occurred while attempting to add you to our Discord server. Please contact us for support at <a class="text-danger" href="mailto:<?php echo EMAIL_SUPPORT; ?>"><?php echo EMAIL_SUPPORT; ?></a>.
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