<?php
require('../template/top.php');
$auth_result = auth(1);

if (!is_array($auth_result)) {
	die(header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']));
}

header("Location: https://discordapp.com/api/oauth2/authorize?client_id=758538949810585641&redirect_uri=https%3A%2F%2Fwww.untrobotics.com%2Fauth%2Fdiscord&response_type=code&scope=identify%20guilds.join");