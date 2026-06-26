<?php
require('../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');

$req = json_decode(file_get_contents('php://input'));

//$a = AdminBot::send_message(substr($raw, 0, 2000), 758208289661124618);
error_log(var_export($req, true));