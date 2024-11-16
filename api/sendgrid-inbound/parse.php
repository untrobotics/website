<?php
require('../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');

AdminBot::send_message('Inbound parse....');
$html = $_POST['html'];

// find mailto
error_log(print_r($html, true));
if (preg_match('@"mailto:(.+?)"@', $html, $email_matches)) {
    $email = $email_matches[1];

    // retrieve name
    preg_match('@<a href="mailto:.+?">(.+?)</a>@', $html, $name_matches);
    $name = $name_matches[1];

    AdminBot::send_message("{$name} ({$email}) has requested to join UNT Robotics on OrgSync :)");

    $token = random_int(100000, 999999);

    $q = $db->query("INSERT INTO orgsync_members (`name`, `email`, `source`, `token`) 
        VALUES
        (
        '" . $db->real_escape_string($name) . "', 
        '" . $db->real_escape_string($email) . "',
        '" . $db->real_escape_string("orgsync/inbound-parse") . "',
        '" . $db->real_escape_string($token) . "'
        )");

    AdminBot::send_message("{$name} ({$email}) db write attempt (" . $token . "): " . $db->insert_id . "/" . $db->affected_rows);

    $email_send_status = email(
        $email,
        "UNT Robotics Membership & Welcome!",

        "<div style=\"position: relative;max-width: 100vw;text-align:center;\">" .
        '<img src="cid:untrobotics-email-header">' .

        '	<div></div>' .

        '<div style="text-align: left; max-width: 500px; display: inline-block;">' .
        "	<p>Dear " . $name . ",</p>" .
        "	<p>Welcome to the team!!</p>" .
        "   <p>Thank you for joining UNT Robotics on orgsync/campuslabs! If you haven't already, please make sure to join our" .
        "      <a href=\"https://www.untrobotics.com/discord\"><b>Discord server</b></a> as this is where we all chat and conduct most of our projects and teams.</p>" .
        "   <p>Once you have joined the Discord server, you may be asked for a verification token, your verification token is:</p>" .
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

    if ($email_send_status) {
        AdminBot::send_message("Successfully sent e-mail with welcome message (" . var_export($email_send_status, true) . ")");
    } else {
        AdminBot::send_message("Failed to send e-mail with welcome message...");
    }
} else {
    AdminBot::send_message('Inbound parse received a suspicious email: ' . file_get_contents("php://input"));
}
