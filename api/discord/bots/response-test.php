<?php

include __DIR__.'/vendor/autoload.php';

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Message;

$discord = new Discord([
    'token' => '',
    'intents' => 8

]);

$discord->on('ready', function (Discord $discord) {

    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
        
        if ($message->content) {}

    });

});

$discord->run();