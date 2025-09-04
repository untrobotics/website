<?php
include __DIR__ . '/vendor/autoload.php';
include __DIR__ . '../../../template/config.php';

use Discord\Builders\Components\TextDisplay;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Button;
use Discord\Builders\Components\Label;
use Discord\Builders\MessageBuilder;
use Discord\Builders\Components\TextInput;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Intents;
use Discord\WebSockets\Event;

$discord = new Discord([
    'token' => DISCORD_ADMIN_BOT_TOKEN,
    'intents' => Intents::getDefaultIntents()
]);

$discord->on('ready', function (Discord $discord) {
    echo "Bot is ready!", PHP_EOL;

    // Listen for messages.
    $discord->on(Event::MESSAGE_CREATE, function (Message $message, Discord $discord) {
        echo "{$message->author->username}: {$message->content}", PHP_EOL;
    });
    $discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction, Discord $discord) {
        switch ($interaction->data->custom_id) {
            case "get-token-button":
                $interaction->showModal('Get Token', 'discord-verification-modal-email', [
                    Label::new("Email",
                        TextInput::new("Email", 1)->setRequired(true)->setCustomId('email')->setMinLength(3)->setMaxLength(254),
                        "Enter a valid UNT email address."
                    )
                ]);
                break;
            case 'check-token-button':
                $interaction->showModal('Verify Token', 'discord-verification-modal-token',[
                    Label::new(
                        "Token",
                        TextInput::new("Token",1)->setRequired(true)->setCustomId('token'),
                        "Enter the token sent to your UNT email address."
                    ),
                ]);
                break;
            case 'discord-verification-modal-email':
                echo json_encode($interaction);
                foreach($interaction->data->components as $component) {
                    if($component->type !== 18) continue;
//                    $email = $component->;
                    $email = $component->component->value;
                }

                break;
            case 'discord-verification-modal-token':
                break;
        }
    });

    $channel = new Channel($discord, ['id' => '948791924406489098', 'guild_id' => '889244868315074560']);
    $getTokenButton = Button::new(
        Button::STYLE_PRIMARY,
        "get-token-button"
    );
    $getTokenButton->setLabel("Get Token");

    $checkTokenButton = Button::new(
        Button::STYLE_PRIMARY,
        "check-token-button"
    );
    $checkTokenButton->setLabel("Verify");

    $channel->sendMessage(MessageBuilder::new()->setContent('')
        ->addEmbed(
            new Embed($discord, [
                'title' => 'Welcome to the UNT Robotics Discord server!',
                'description' => 'Before you can interact with our server, you’ll need to complete our quick verification process. This helps us keep the server safe and free from spam.' . PHP_EOL . PHP_EOL .
                    'If you want to continue the verification process in Discord, read on. Otherwise, you can verify yourself on our [website](https://www.untrobotics.com/login)' . PHP_EOL . PHP_EOL .
                    'To verify yourself, you\'ll need a valid UNT email address and a token we\'ll be sending to that address.' . PHP_EOL . PHP_EOL .
                    'First, click the "Get Token" button below. After submitting your email address, or if you already have a token, click the "Verify" button.',
                'color' => '#059033',
            ])
        )
        ->addComponent(ActionRow::new()
            ->addComponent(
                $getTokenButton,
            )->addComponent(
                $checkTokenButton,
            )
        )
    );
});

$discord->run();