<?php
function get_last_three_tweets() {
    return;
    require_once(__DIR__ . '/../../template/config.php');

    $context = stream_context_create(array(
        'http' => array(
            'ignore_errors' => true,
            'header' => 'User-Agent: Mozilla/5.0 (BB10; Kbd) AppleWebKit/537.35+ (KHTML, like Gecko) Version/10.3.3.2205 Mobile Safari/537.35+'
        )
    ));
    $twitter = file_get_contents('https://mobile.twitter.com/' . SOCIAL_MEDIA_TWITTER_HANDLE, false, $context);
    //var_dump($http_response_header);

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

    return '<div class="untr_tweet_container text-left">' . $result . '</div>';
}