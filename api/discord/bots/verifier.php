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
		// bot doesn't need special intents
		$this->discord = new Discord([
			'token' => $bot_token,
			'intents' => Intents::getDefaultIntents()
		]);

		// runs when the bot establishes a connection with Discord
		$this->discord->on('ready', function (Discord $discord) {
			// adds event listener for when an Interaction is started. Interactions are things like slash commands or clicking a button on the bot's message
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
        // ignore interactions which don't provide data
        if(is_null($interaction->data)){
            error_log("Discord verification bot received an unknown interaction initiated by " . $interaction->user->id . " in guild " . $interaction->guild->id . " and channel " . $interaction->channel->id . ".");
            return;
        }
        // handle interactions based on interaction names
        switch ($interaction->data->name) {
            case "verify-email":
                self::ValidateEmail($interaction);
                break;
            case "verify-token":
                self::ValidateToken($interaction);
                break;
            default:
                error_log("Discord verification bot received an unknown interaction with name " . $interaction->data->name);
        }
    }

    /**
     * Validate a /verify-email interaction
     * @param Interaction $interaction
     * @return void
     */
    private static function ValidateEmail(Interaction $interaction){
		// don't do the process if the user has the role already
		if($interaction->member->roles->has(DISCORD_VERIFIED_ROLE_ID)){
			$interaction->respondWithMessage(
				MessageBuilder::new()->setContent("You already have the verified role.")
			);
			return;
		}

		// get email passed to the bot
        $email = $interaction->data->options["email"]->value;
        // if email isn't a UNT email, provide error message to user and end interaction
        if(!self::IsValidEmail($email)){
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent(
                    "Invalid email address provided. Make sure the email is a valid UNT email. If you're having issues verifying yourself, contact an officer.". PHP_EOL . PHP_EOL . "Email: ``{$email}``"
                ),
                true
            );
            return;
        }

        // user sees an ephemeral, loading message from the bot
        $interaction->acknowledgeWithResponse(true)->then(
            function () use($interaction, $email) {
				global $db;
				$escaped_email = $db->real_escape_string($email);
				$discord_id = $interaction->user->id;

				// check DB to see if the user and email have already been linked together
				//todo should this be a discord_id check only?
				$q = $db->query("
					SELECT 
					    email, discord_id 
					FROM 
					    users
					WHERE
						email = '{$escaped_email}'
					AND
					    discord_id = {$discord_id}
				");
				if($q === false){
					error_log("Failed to update DB with verification token for <@" . $discord_id . "> (email: {$email}): " . $db->error);
					$interaction->updateOriginalResponse(
						MessageBuilder::new()->setContent("Could not generate verification token." . PHP_EOL . PHP_EOL . "If this issue persists, contact an officer.")
					);
					return;
				}
				if($q->num_rows > 0) {
					self::AssignVerfiedRole($interaction, null);
				}
				//generate token
                $token = bin2hex(openssl_random_pseudo_bytes(3));

				// add token to discrd_verification_tokens table
				//inserts the token with the email and discord id
				// expiration date is 7 days from insertion
				$q = $db->query(
                    "INSERT INTO 
    							discord_verification_tokens (
								  	token, 
								 	expires_on, 
								  	discord_id, 
								 	unt_email
							  	) 
							VALUES (
									'{$token}', 
							        CURRENT_TIMESTAMP() + INTERVAL 7 DAY, 
							        $discord_id, 
							        '{$escaped_email}'
							)"
                );

				// if token couldn't be added to the table, respond with an error message, and log db error for devs to see
				if (!$q) {
                    $response = "Could not generate verification token." . PHP_EOL . PHP_EOL . "If this issue persists, contact an officer.";
                    error_log("Failed to update DB with verification token for <@" . $discord_id . "> (email: {$email}): " . $db->error);
                } else {
                    // Send generic welcome email
                    $email_send_status = email(
                        $email,
                        "UNT Robotics Discord verification token",

                        "<div style=\"position: relative;max-width: 100vw;text-align:center;\">" .
                        '<img src="cid:untrobotics-email-header">' .

                        '	<div></div>' .

                        '<div style="text-align: left; max-width: 500px; display: inline-block;">' .
                        "	<p>Hey there!</p>" .
                        "	<p>Welcome to the team!!</p>" .
                        "   <p>Thank you for joining the UNT Robotics Discord server!" .
                        "   <p>Your verification token is:</p>" .
                        "</div>" .
                        "<div>" .
                        "   <p style='font-size: 20pt; font-weight: 900; margin-top: 10px;'>{$token}</p>" .
                        '</div>' .

                        '	<div></div>' .

                        "	<p></p>" .

                        "	<p>If you need any assistance, please reach out to <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a>.</p>" .

                        '	<div></div>' .

                        '<div style="text-align: left; width: 500px; display: inline-block;">' .
                        "	<p>All the best,</p>" .
                        "   <p><em>UNT Robotics Leadership</em></p>" .
                        '</div>' .

                        "</div>",

                        "hello@untrobotics.com",
                        null,
                        [
                            [
                                'content' => base64_encode(file_get_contents(BASE . '/images/unt-robotics-email-header.jpg')),
                                'type' => 'image/jpeg',
                                'filename' => 'unt-robotics-email-header.jpg',
                                'disposition' => 'inline',
                                'content_id' => 'untrobotics-email-header'
                            ]
                        ]
                    );

                    // if the email was successfully sent, respond with a success message.
                    if ($email_send_status) {
                        $response = "Email sent to {$email}. Use ``/verify-token <token>`` to continue the verification process. Tokens are valid for 7 days." . PHP_EOL . PHP_EOL . "If you can't find the email, check your spam folder.";
                    } else {
                        // if email wasn't sent, respond with an error message and remove the token from the db.
                        $response = "Could not send an email to {$email}." . PHP_EOL . PHP_EOL . "If this issue persists, contact an officer.";
                        $q = "DELETE FROM discord_verification_tokens WHERE token = '{$token}' AND discord_id = '{$discord_id}'";
						if($q === false) {
							error_log("Failed to send verification email with a token to {$email}. Also failed to remove the new token entry from the tokens table.");
						}
                        error_log("Failed to send verification email with a token to {$email}.");
                    }
                }

                // update loading message with proper response
                $interaction->updateOriginalResponse(
                    MessageBuilder::new()->setContent($response)
                );
            }
        );
    }

    /**
     * Validate a /verify-token interaction
     * @param Interaction $interaction
     * @return void
     */
    private static function ValidateToken(Interaction $interaction){
		// if user is already verified (has the verified role), tell them they already have the role and end the interaction
		if($interaction->member->roles->has(DISCORD_VERIFIED_ROLE_ID)){
			$interaction->respondWithMessage(
				MessageBuilder::new()->setContent("You already have the verified role.")
			);
			return;
		}

		global $db;
        // get token passed to the bot
		// $token sanitized for db since it won't be used elsewhere
        $token = $interaction->data->options["token"]->value;
        $user_id = $interaction->user->id;
        // if token isn't valid, tell user verification failed and end interaction
        if(!self::IsValidToken($token)){
            $interaction->respondWithMessage(
                MessageBuilder::new()->setContent(
                    "Verification failed."
                ),
                true
            );
            return;
        }
		//todo can we check for discord_id in users table?
        // fetch token from db where the user's discord id and token provided match
        $q = $db->query("SELECT * FROM discord_verification_tokens WHERE token = '{$token}' AND discord_id = '{$interaction->user->id}'");
        // if query fails, respond with an error message, log the db error, and end the interaction
		if ($q === false) {
			$interaction->respondWithMessage(
				MessageBuilder::new()->setContent(
					"Verification failed due to internal server error. Contact an officer if this issue persists.."
				),
				true
			);
			error_log("Failed to fetch token ({$token}) from discord verification table for user {$user_id}: {$db->error}");
			return;
		}
		// if no entries match token-discord_id combo then fail verification
		if ($q->num_rows < 1) {
			$interaction->respondWithMessage(
				MessageBuilder::new()->setContent(
					"Verification failed."
				),
				true
			);
			return;
		}

		$r = $q->fetch_array(MYSQLI_ASSOC);
		// user_id isn't null if the token was linked to a user
		if ($r['user_id'] != null) {
			$interaction->respondWithMessage(
				MessageBuilder::new()->setContent(
					"Verification failed."
				),
				true
			);
			return;
		}

		self::AssignVerfiedRole($interaction, $token);
		}

	/**
	 * Acknowledges the interaction with a loading message, then tries to add the verified role to the user.
	 * Logs an error if the role update failed.
	 * @param Interaction $interaction
	 * @param string|null $token
	 * @return void
	 */
	public static function AssignVerfiedRole(Interaction $interaction, mixed $token): void
	{
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
					if($token !== null) {
						error_log("Error adding the verified role to user <@{$interaction->user->id}> (ID: {$interaction->user->id}; token: {$token}): $reason");
					} else {
						error_log("Error adding the verified role to user <@{$interaction->user->id}> (ID: {$interaction->user->id}; no token required): $reason");
					}

				}
			);
		});
	}

    /**
     * Verify that an email address is a my.unt email
     * @param string $email The email to validate
     * @return bool True if the email is a UNT email. False otherwise
     */
    private static function IsValidEmail(string $email): bool{
        // regex for the domain name
        $valid_domains = '/@my\.unt\.edu$/i';
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