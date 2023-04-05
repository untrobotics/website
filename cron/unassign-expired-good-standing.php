<?php
require('../template/top.php');
require(BASE . '/api/discord/bots/admin.php');

echo "<pre>";

$users = AdminBot::get_all_users();

// TODO: track success status as messages are sent!
$discord_users = $users->result;
foreach ($discord_users as $key => $val) {
    if (in_array(DISCORD_GOOD_STANDING_ROLE_ID, $val->roles)) {

        $user = $untrobotics->get_user_by_discord_id($val->user->id);
        $is_good_standing = $untrobotics->is_user_in_good_standing($user);

        if (!$is_good_standing) {
            echo $key . ": {$val->user->username}#{$val->user->discriminator} ({$val->user->id}) [{$user['id']}]" . PHP_EOL;

            AdminBot::remove_user_role($val->user->id, DISCORD_GOOD_STANDING_ROLE_ID);
            foreach (DISCORD_GOOD_STANDING_DEPENDENT_ROLES as $role) {
                AdminBot::remove_user_role($val->user->id, $role);
            }

            $id = AdminBot::create_dm($val->user->id);

            do {
                $result = AdminBot::send_message(
                    "Hello! This is the UNT Robotics administrative bot.\n" .
                    "Your _Good Standing_ role has expired and has been removed from your account. If you had any other roles in the server which required _Good Standing_, those have been removed as well.\n" .
                    "If you would like to regain the _Good Standing_ role and any other roles you had, please pay your dues for this semester at https://untro.bo/dues.\n" .
                    "If you have any questions or concerns, or if your _Good Standing_ role should not have expired, please ask an officer in the #help-and-support channel (https://discord.gg/CvrRsqndEf).",
                    $id);

                if (DiscordBot::hasHitRateLimit($result)) {
                    $waitTime = intval($result->result->retry_after / 1000) + 1;
                    echo "Hit rate limit, waiting for $waitTime seconds ({$result->result->retry_after})" . PHP_EOL;
                    sleep($waitTime);
                } else {
                    echo "Successfully sent message to {$val->user->username}#{$val->user->discriminator}" . PHP_EOL;
                }
            } while (DiscordBot::hasHitRateLimit($result));

            sleep(4);
        }
    }
}

// rogues! let's find people who have privileged roles but do not have a Good Standing role
echo "--" . PHP_EOL;


/* exempt users:
Admin Bot: 758538949810585641
Dev Admin Bot: 765776425176399902
*/
//foreach ($discord_users as $key => $val) {
//    $user = $untrobotics->get_user_by_discord_id($val->user->id);
//    $is_good_standing = $untrobotics->is_user_in_good_standing($user);
//
//    if (!$is_good_standing && array_intersect(DISCORD_GOOD_STANDING_DEPENDENT_ROLES, $val->roles)) {
//        echo $key . ": {$val->user->username}#{$val->user->discriminator} ({$val->user->id}) [{$user['id']}]" . PHP_EOL;
//
//        AdminBot::remove_user_role($val->user->id, DISCORD_GOOD_STANDING_ROLE_ID);
//        foreach (DISCORD_GOOD_STANDING_DEPENDENT_ROLES as $role) {
//            AdminBot::remove_user_role($val->user->id, $role);
//        }
//    }
//}
?>