<?php
require('../template/top.php');
$auth = auth();
if ($auth === false) {
    http_response_code(401);
    echo 'INVALID_LOGIN';
    die();
}
$auth = $auth[0];
if (!isset($_POST)) {
    die();
}
$dataType = $_POST['type'];
switch ($dataType) {
    default:
    {
        echo 'INVALID_REQUEST';
        die();
    }
    case "email":
    {
        $email = $_POST['email'];
        // validate for my.unt.edu email
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || preg_match('/@my\.unt\.edu$/i', $email) !== 1) {
            echo 'INVALID_EMAIL';
            die();
        }
        global $db;
        // fetch latest token
        $q = $db->query("SELECT 
                            id, 
                            created_on 
                        FROM 
                            discord_verification_tokens 
                        WHERE 
                            user_id = {$auth['id']}
                        ORDER BY created_on DESC
                        LIMIT 1");
        if ($q === false) {
            error_log("Could not retrieve discord verification token for user {$auth['id']}: {$db->error}");
            echo 'ERROR';
            die();
        }
        if ($q->num_rows > 0) {
            $r = $q->fetch_assoc();
            /** @noinspection PhpParenthesesCanBeOmittedForNewCallInspection */
            /** @noinspection PhpUnhandledExceptionInspection */
            if ((new DateTime())->sub(new DateInterval('PT' . EMAIL_TOKEN_GENERATION_RATE_LIMIT . 'S')) < new DateTime($r['created_on'])) {
                echo 'TOKEN_RATELIMIT';
                die();
            }
            $q = $db->query("DELETE FROM 
                                discord_verification_tokens 
                            WHERE 
                                id = {$r['id']}"
            );
            if($q === false){
                error_log("Could not delete discord verification token {$r['id']} for user {$auth['id']}: {$db->error}");
                echo 'ERROR';
                die();
            }
        }
        // generate token
        $token = bin2hex(openssl_random_pseudo_bytes(3));
        $q = $db->query("INSERT INTO discord_verification_tokens (
                                         token,
                                         created_on,
                                         user_id,
                                         unt_email
                                         )
                        VALUES (
                                '{$token}',
                                CURRENT_TIMESTAMP(),
                                {$auth['id']},
                                '{$db->real_escape_string($email)}'
                        )"
        );
        if ($q === false) {
            error_log("Could not add discord verification token to db for user {$auth['id']}: {$db->error}");
            echo 'ERROR';
            die();
        }
        // send email with token
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
        if($email_send_status === false) {
            error_log("Failed to email verification token to {$email}.");
            echo 'ERROR';
            // delete newly generated token from db since we couldn't email it
            $q = $db->query("DELETE FROM 
                            discord_verification_tokens
                        WHERE
                            token = '{$token}'
                        AND
                            user_id = {$auth['id']}");
            if($q === false){
                error_log("Could not delete discord verification token {$token} for user {$auth['id']}: {$db->error}");
            }
            die();
        }
        // changes the form to send a token instead of email
        echo 'TOKEN';
        break;
    }
    case "token":
    {
        $token = $_POST['token'];
        if (ctype_xdigit($token) || strlen($token) !== 6) {
            echo 'INVALID_TOKEN';
            die();
        }
        global $db;
        // check if there's an unexpired token for the user
        $q = $db->query("SELECT 
                            unt_email
                        FROM
                            discord_verification_tokens
                        WHERE 
                            token = '{$token}'
                        AND 
                            user_id = {$auth['id']}
                        AND
                            created_on + INTERVAL 7 day > CURRENT_TIMESTAMP()");
        if($q === false){
            error_log("Failed to fetch token for user {$auth['id']}: {$db->error}");
            echo 'ERROR';
            die();
        }
        if ($q->num_rows === 0) {
            echo 'INVALID_TOKEN';
            die();
        }
        // add unt_email to users entry
        $email = $q->fetch_column();
        $q = $db->query("UPDATE
                            users
                        SET 
                            unt_email = '{$db->real_escape_string($email)}'
                        WHERE 
                            id = {$auth['id']}");
        if($q === false){
            error_log("Failed to add UNT email ({$email}) to user {$auth['id']}: {$db->error}");
            echo 'ERROR';
            die();
        }

        // deletes the form and shows the discord link
        echo '<a href="/join/w/discord">Click here to update your Discord account status.</a>';
        break;
    }
}