<?php
function get_last_three_tweets() {
	$twitter = file_get_contents('https://mobile.twitter.com/untrobotics');

	preg_match_all('@<table class="tweet  "(.+?)</table>@ism', $twitter, $m);

	$result = "";
	for ($i = 0; $i < 3; $i++) {
		$val = $m[0][$i];
		$val = preg_replace('@<tr>.+<td colspan="2" class="meta-and-actions">.+</td>.+</tr>@ism', '', $val);
		preg_match('@<td class="timestamp">(.+?)</td>@ism', $val, $timestamp);
		//var_dump($timestamp[1]);
		$val = preg_replace('@<td class="timestamp">(.+?)</td>@ism', '', $val);
		$val = str_replace('<tr class="tweet-container">', '<tr><td>' . $timestamp[1] . '</td></tr><tr class="tweet-container">', $val);
		//$val = str_replace('<a href="/hashtag/', '<a href="//www.twitter.com/hashtag/', $val);
		$val = preg_replace('@href="/@i', 'href="//twitter.com/', $val);
		$val = str_replace('<td class="avatar" rowspan="3">', '<td class="avatar" rowspan="2">', $val);
		
		$result .= $val;
	}
	
	return '<div class="untr_tweet_container">' . $result . '</div>';
}