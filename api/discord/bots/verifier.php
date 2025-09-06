<?php
require_once(__DIR__ . "/../../../template/sample.config.php");
require_once(__DIR__ . "/../../../template/top.php");
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;

class DiscordWebhook
{
    private $discord;

    /**
     * Creates a DiscordWebhook instance. To start the webhook, call {@see DiscordWebhook::run()}
     * @param string $bot_token The super secret bot token
     * @throws IntentException
     */
    public function __construct($bot_token = DISCORD_ADMIN_BOT_TOKEN)
    {
        $this->discord = new Discord([
            'token' => $bot_token,
            'intents' => Intents::getDefaultIntents()
        ]);

        // runs when the bot establishes a connection with Discord
        $this->discord->on('ready', function (Discord $discord) {
//            echo "Bot is ready!", PHP_EOL;

            $discord->on(Event::INTERACTION_CREATE, DiscordWebhook::AcknowledgeInteraction(...));
        });
    }

    /**
     * Starts the webhook.
     */
    public function run(){
        $this->discord->run();
    }

    /**
     * Handler for {@see Event::INTERACTION_CREATE} events.
     * @param Interaction $interaction
     * @param Discord $discord
     * @return void
     */
    private static function AcknowledgeInteraction(Interaction $interaction, Discord $discord) {
        echo "Received interaction: " . PHP_EOL . json_encode($interaction) . PHP_EOL;
        if(is_null($interaction->data)){
            echo "Unknown interaction" . PHP_EOL;
            return;
        }
        switch ($interaction->data->name) {
            case "verify-email":
                /** @noinspection PhpUndefinedFieldInspection */
                self::ValidateEmail($interaction);
                break;
            case "verify-token":
                self::ValidateToken($interaction);
                break;
            default:
                echo $interaction->data->name . PHP_EOL;
        }
    }

    /**
     * Validate a /verify-email interaction
     * @param Interaction $interaction
     * @return void
     */
    private static function ValidateEmail(Interaction $interaction){
        $email = $interaction->data->options["email"]->value;
        if(!self::IsValidEmail($email)){
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent(
                    "Invalid email address provided. Make sure the email is a valid UNT email. If you're having issues verifying yourself, contact an officer.". PHP_EOL . PHP_EOL . "Email: ``{$email}``"
                ),
                true
            );
            return;
        }
        $interaction->acknowledgeWithResponse(true);
        //todo: send email
        $email_sent = true;
        if($email_sent){
            $response = "Email sent to {$email}. Use ``/verify-token <token>`` to continue the verification process. Tokens are valid for 7 days." . PHP_EOL . PHP_EOL . "If you can't find the email, check your spam folder.";
        } else {
            $response = "Error sending the email to {$email}: {$email_sent}." . PHP_EOL . PHP_EOL . "If this issue persists, contact an officer.";
            error_log($email_sent);
        }
        $interaction->updateOriginalResponse(
            MessageBuilder::new()->setContent($response)
        );
    }

    /**
     * Validate a /verify-token interaction
     * @param Interaction $interaction
     * @return void
     */
    private static function ValidateToken(Interaction $interaction){
        $token = $interaction->data->options["token"]->value;
        if(!self::IsValidToken($token)){
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent(
                    "Verification failed."
                ),
                true
            );
            return;
        }
        //todo: check db
        global $db;

        $q = $db->query("SELECT * FROM ");
        if($q === false){
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent(
                    "Verification failed due to internal server error. Contact an officer if this issue persists.."
                ),
                true
            );
//                    error_log("Failed to fetch token from user verification table: {$db->error}");
            return;
        } /*elseif($q && $q->num_rows > 0) {
            $r = $q->fetch_array(MYSQLI_ASSOC);
            if($token === $r['token']){
                $interaction->acknowledgeWithResponse(true)->then(function () use ($interaction, $token){
                    $interaction->member->addRole($verified_role_id)->then(function () use ($interaction){
                        $interaction->updateOriginalResponse(
                            MessageBuilder::new()->setContent(
                                "You've successfully verified your email address. Welcome to the server."
                            ),
                        );
                    },
                        function ($reason) use ($interaction, $token){
                            $interaction->updateOriginalResponse(
                                MessageBuilder::new()->setContent(
                                    "There was an issue trying to adding the verified role to you. Contact an officer with your token to get your roles updated."
                                ),
                            );
                            error_log("Error adding the verified role to user <@{$interaction->user->id}> (ID: {$interaction->user->id}; token: {$token}): $reason");
                        }
                    );
                });
                return;
            }
        }*/
        else{
            if($interaction->member->roles->has(DISCORD_VERIFIED_ROLE_ID)){
                // user already has role
                $interaction->respondWithMessage(
                    MessageBuilder::new()->setContent("You already the verified role.")
                );
                return;
            }
            $interaction->acknowledgeWithResponse(true)->then(function () use ($interaction, $token) {
                $interaction->member->addRole(DISCORD_VERIFIED_ROLE_ID)->then(function () use ($interaction) {
                    $interaction->updateOriginalResponse(
                        MessageBuilder::new()->setContent(
                            "You've successfully verified your email address. Welcome to the server."
                        ),
                    );
                },
                    function ($reason) use ($interaction, $token) {
                        $interaction->updateOriginalResponse(
                            MessageBuilder::new()->setContent(
                                "There was an issue trying to adding the verified role to you. Contact an officer with your token to get your roles updated."
                            ),
                        );
                        error_log("Error adding the verified role to user <@{$interaction->user->id}> (ID: {$interaction->user->id}; token: {$token}): $reason");
                    }
                );
            });
        }
    }

    /**
     * Verify that an email address is a UNT email
     * @param string $email The email to validate
     * @return bool True if the email is a UNT email. False otherwise
     */
    private static function IsValidEmail(string $email): bool{
        // regex for the domain name
        $valid_domains = '/@(?:my\.)?unt\.edu$/i';
        return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match($valid_domains, $email) === 1;
    }

    /**
     * Verify that a token is in the accepted format (i.e., hexadecimal)
     * @param string $token The token to validate
     * @return bool True if the token is in the accepted format. False otherwise
     */
    private static function IsValidToken(string $token): bool{
        return ctype_xdigit($token);
    }
}