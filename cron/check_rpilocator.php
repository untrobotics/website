<?php
require('../api/rss_feed.php');
require('../api/discord/bots/admin.php');
require_once("../template/config.php");

// urls can be generated at the bottom of https://rpilocator.com/about.cfm
// only pi zeroes: https://rpilocator.com/feed/?cat=PIZERO
// only pis in US: https://rpilocator.com/feed/?country=US
// only pi zeroes in US: https://rpilocator.com/feed/?country=US&cat=PIZERO
const URL = 'https://rpilocator.com/feed/?country=US&cat=PI3,PI4,PIZERO';

$feed = new rss_feed(URL,true);
$time_now = new DateTime('now', new DateTimeZone('GMT'));	// Post dates are in GMT (or UTC, but well... whatever)

foreach($feed->posts as $post)
{
	// diff gives the difference between 2 DateTimes as a whole (so 2022/01/01 - 2021/12/31 gives 0000/00/01 instead of 0001/-11/-30)
	$time_diff = $time_now->diff($post->date, true);

	// Would it short-circuit better with minutes+seconds first and then hours?
	if( $time_diff->h == 0	//hour difference is 0
		&& $time_diff->i*60+$time_diff->s <= 120	// Seconds (minutes+seconds) difference is leq 120 seconds
		&& $time_diff->d == 0	// Day difference is 0
		&& $time_diff->m == 0	// Month difference is 0
		&& $time_diff->y == 0	// Year difference is 0
	)
	{
		AdminBot::send_message($post->title, DISCORD_HPR_BOT_SPAM_CHANNEL_ID);
	}
	else
	{
		//posts are always sorted in order from newest to oldest, so we can stop checking after this
		break;
	}
}