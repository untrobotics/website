<?php
/**
 * Latest Discord announcements for the site footer (replaces the dead Twitter
 * feed). Pulls the most recent messages from the announcements channel via the
 * Discord REST API using the bot token, caches them briefly, and returns HTML.
 *
 * Read-only: needs the bot to have View Channel + Read Message History on the
 * announcements channel (it already does as a guild member).
 */

require_once(__DIR__ . '/../../template/config.php');

function get_last_three_announcements($limit = 3) {
    $channel_id = defined('DISCORD_ANNOUNCEMENTS_CHANNEL_ID') && DISCORD_ANNOUNCEMENTS_CHANNEL_ID
        ? DISCORD_ANNOUNCEMENTS_CHANNEL_ID : '757730622843125831';
    $guild_id = defined('DISCORD_GUILD_ID') && DISCORD_GUILD_ID
        ? DISCORD_GUILD_ID : '639209564188704768';
    $token = defined('DISCORD_ADMIN_BOT_TOKEN') ? DISCORD_ADMIN_BOT_TOKEN : '';
    if ($token === '') {
        return '';
    }

    $cache_file = sys_get_temp_dir() . '/untr_discord_announcements.json';
    $cache_ttl = 600; // 10 minutes

    $messages = null;
    if (is_readable($cache_file) && (time() - filemtime($cache_file) < $cache_ttl)) {
        $messages = json_decode(file_get_contents($cache_file), true);
    }

    if (!is_array($messages)) {
        // Fetch a few extra so we can skip empty (image/embed-only) messages.
        $ch = curl_init('https://discord.com/api/v10/channels/' . $channel_id . '/messages?limit=10');
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bot ' . $token,
                'User-Agent: UNTRoboticsSite (https://www.untrobotics.com, 1.0)',
            ),
        ));
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200 && $resp) {
            $decoded = json_decode($resp, true);
            if (is_array($decoded)) {
                $messages = $decoded;
                @file_put_contents($cache_file, $resp);
            }
        }
        // On API error, fall back to any (stale) cached copy.
        if (!is_array($messages) && is_readable($cache_file)) {
            $messages = json_decode(file_get_contents($cache_file), true);
        }
    }

    if (!is_array($messages) || !count($messages)) {
        return '';
    }

    $out = '';
    $shown = 0;
    foreach ($messages as $m) {
        if ($shown >= $limit) {
            break;
        }
        $content = isset($m['content']) ? trim($m['content']) : '';
        if ($content === '') {
            continue; // skip attachment/embed-only posts
        }

        // Clean Discord-specific markup so the public feed reads cleanly.
        $content = preg_replace('/@(everyone|here)\b/i', '', $content);  // drop mass-ping text
        $content = preg_replace('/<a?:(\w+):\d+>/', ':$1:', $content);   // custom emoji -> :name:
        $content = preg_replace('/<@&\d+>/', '', $content);             // role mentions
        $content = preg_replace('/<@!?\d+>/', '@member', $content);     // user mentions
        $content = preg_replace('/<#\d+>/', '#channel', $content);      // channel mentions
        $content = trim($content);
        if ($content === '') {
            continue;
        }

        // Escape, then linkify URLs and trim to a footer-friendly length.
        $safe = htmlspecialchars($content, ENT_QUOTES);
        if (strlen($safe) > 200) {
            $safe = rtrim(substr($safe, 0, 200)) . '&hellip;';
        }
        $safe = preg_replace(
            '@(https?://[^\s<]+)@',
            '<a href="$1" class="text-white" target="_blank" rel="noopener">$1</a>',
            $safe
        );
        $safe = nl2br($safe);

        $ts = isset($m['timestamp']) ? strtotime($m['timestamp']) : 0;
        $when = $ts ? date('M j, Y', $ts) : '';
        $msg_id = isset($m['id']) ? $m['id'] : '';
        $link = 'https://discord.com/channels/' . $guild_id . '/' . $channel_id . '/' . rawurlencode($msg_id);

        $out .= '<article class="event offset-top-20">'
              . '<p class="text-white">' . $safe . '</p>'
              . '<time class="small offset-top-10"><a href="' . htmlspecialchars($link)
              . '" class="text-white" target="_blank" rel="noopener">' . htmlspecialchars($when)
              . ' &middot; view in Discord</a></time>'
              . '</article>';
        $shown++;
    }

    return $out;
}
