<?php
//require(__DIR__ . '/../template/top.php');
//require(BASE . '/api/discord/bots/admin.php');
//
//$rateLimitWaitTimeSeconds = 10;
//
//$mentions = array(
//    "seb" => "95711383059959808",
//    "joe" => "540731968594378763",
//    "kal" => "318781909276688384",
//    "brooke" => "764223398569705524",
//    "abdullah" => "416843686362480658",
//    "cameron" => "381861068139528195",
//    "ben" => "801555345167745034",
//    "andrew" => "595832901187272704",
//    "ali" => "363115900452470788",
//    "kenneth" => "877618983401037854",
//    "jacob" => "347860152067817493",
//    "ibi" => "381304674105556993",
//    "nick" => "379723128366170112",
//    "lauren" => "540260611028811777"
//);
//
//$pre_reminder_array = array(
//    "Seb" => [
//    ],
//    "Joe" => [
//        "add aerospace merch"
//    ],
//    "Brooke" => [
//    ],
//    "Abdullah" => [
//    ],
//    "Cameron" => [
//    ],
//    "Ben" => [
//    ],
//    "Andrew" => [
//    ],
//    "Kenneth" => [
//    ],
//    "Jacob" => [
//    ],
//    "Ibi" => [
//        "Get us access to C119",
//        "Move everything from F232A to C119",
//        "Email Sarah about the spark stuff + cabinet",
//        "follow up with UNT Fire Marshall and set up a meeting",
//        "follow up with RMS Workshop dept"
//    ],
//    "Nick" => [
//    ]
//);
//
//$reminders = array("DAILY REMINDERS! Please tell Seb if you want something added to this list.\n");
//AdminBot::send_message(join("\n\n", $reminders), 1003673992730775724);
//
//foreach ($pre_reminder_array as $name => $reminder_array) {
//    do {
//        if (count($reminder_array) == 0) {
//            $reminders = "**{$name}:** ...";
//        } else {
//            $mention_key = strtolower($name);
//
//            $reminders = "**<@{$mentions[$mention_key]}>:** \n" . join(PHP_EOL, $reminder_array);
//        }
//        $result = AdminBot::send_message($reminders . "\n\n", 1003673992730775724);
//        if (DiscordBot::hasHitRateLimit($result)) {
//            $waitTime = intval($result->result->retry_after / 1000) + 1;
//            echo "Hit rate limit, waiting for $waitTime seconds ({$result->result->retry_after})" . PHP_EOL;
//            sleep($waitTime);
//        }
//    } while (DiscordBot::hasHitRateLimit($result));
//}
//
////$hpr_channel_id = 834922880415432754;
////$reminders = array("DAILY REMINDERS! Please tell Seb if you want something added to this list.\n");
////$reminders[] = "**Joe:** start the FRR addendum
////NASA Forms
////check gateway until all the offers go through please";
////$reminders[] = "**Seb:** review Nick's code";
////$reminders[] = "**Nick:** ?";
////$reminders[] = "**Tommie:** ?";
////$reminders[] = "**Ben:** ?";
////$reminders[] = "**Ali:** ?";
////$reminders[] = "**Ibi:** ?";
////$reminders[] = "**Payload Team:** Successfully test drone (**by FRIDAY 3/25**)
////Confirm code citation issues are resolved with Nick";
////AdminBot::send_message(join("\n\n", $reminders), $hpr_channel_id);
