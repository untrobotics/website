<?php

chdir(__DIR__);

require(__DIR__ . '/../api/campuslabs/total-members.php');
require(__DIR__ . '/../api/groupme-funcs.php');

$file = __DIR__ . '/total-members.txt';

$prev_members = intval(file_get_contents($file));
$members = total_members();

if ($members != $prev_members) {
	echo "Number of members has changed from {$prev_members} to {$members}." . PHP_EOL;
	file_put_contents($file, $members);
	$diff = abs($members - $prev_members);
	if ($members > $prev_members) {
		echo "We have gained {$diff} member(s)!";
		post_message("We have gained {$diff} member(s)!", GROUPME_OFFICER_CHANNEL_ID);
	} else {
		echo "We have lost {$diff} member(s)!";
		post_message("We have lost {$diff} member(s)!", GROUPME_OFFICER_CHANNEL_ID);
	}
} else {
	echo "Number of members has remained at {$members}." . PHP_EOL;
}
