<?php
require('../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');

// --- Shared-secret gate ------------------------------------------------------
// This endpoint inserts a member row and sends a welcome email, so it must not
// be open to the public internet. Only the mail relay's OrgSync ingest pipe
// (mail/orgsync-ingest.py) may call it; it sends the shared secret in the
// X-Ingest-Secret header. Reject anything that doesn't match (and never allow
// through when INGEST_SECRET is unconfigured — fail closed).
$__ingest_secret_expected = defined('INGEST_SECRET') ? INGEST_SECRET : '';
$__ingest_secret_actual   = $_SERVER['HTTP_X_INGEST_SECRET'] ?? '';
if ($__ingest_secret_expected === ''
    || !hash_equals($__ingest_secret_expected, $__ingest_secret_actual)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden\n";
    exit;
}

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
        "<p>Dear " . htmlspecialchars($name) . ",</p>" .
        "<p>Welcome to the team!</p>" .
        "<p>Thank you for joining UNT Robotics on OrgSync/CampusLabs! If you haven't already, please join our " .
        "<a href=\"https://www.untrobotics.com/discord\"><strong>Discord server</strong></a> — it's where we chat and run most of our projects and teams.</p>" .
        "<p>Once you've joined the Discord server, you may be asked for a verification token. Yours is:</p>" .
        brand_email_code_box($token) .
        "<p>If you need any assistance, reach out to <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a>.</p>" .
        "<p>All the best,<br><em>UNT Robotics Leadership</em></p>",
        "hello@untrobotics.com"
    );

    if ($email_send_status) {
        AdminBot::send_message("Successfully sent e-mail with welcome message (" . var_export($email_send_status, true) . ")");
    } else {
        AdminBot::send_message("Failed to send e-mail with welcome message...");
    }
} else {
    AdminBot::send_message('Inbound parse received a suspicious email: ' . file_get_contents("php://input"));
}
