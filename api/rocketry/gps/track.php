<?php
require("../../discord/bots/admin.php");

$payload = file_get_contents('php://input');
$json = json_decode($payload);

$device_id = $json->device_id;
error_log($payload);
file_put_contents("locations/$device_id", $payload);

//AdminBot::send_message("A new rocket has come online and is being tracked: $payload. Track here: https://www.untrobotics.com/api/rocketry/gps/locate?name={$json['device_id]}", 834922880415432754);
