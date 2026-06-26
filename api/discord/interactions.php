<?php
require("../../template/top.php");
require("interactions/Interaction.php");
require("interactions/InteractionResponseType.php");

require("bots/admin.php");

$CLIENT_PUBLIC_KEY = DISCORD_CLIENT_PUBLIC_KEY;

$signature = $_SERVER['HTTP_X_SIGNATURE_ED25519'];
$timestamp = $_SERVER['HTTP_X_SIGNATURE_TIMESTAMP'];
$postData = file_get_contents('php://input');

AdminBot::send_message("Received interaction: ", $postData);

if (Interaction::verifyKey($postData, $signature, $timestamp, $CLIENT_PUBLIC_KEY)) {
    echo json_encode(array(
        'type' => InteractionResponseType::PONG
    ));

    AdminBot::send_message("Received interaction: ", $postData);
} else {
    http_response_code(401);
    echo "Not verified";
}
?>