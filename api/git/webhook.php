<?php
/**
 * GitHub webhook -> Discord (#webmasters). Posts:
 *   - push to master  -> a prod-deploy summary (prod tracks master).
 *   - pull_request     -> PR opened / merged.
 *   - workflow_run     -> CI failures (failures only, master/develop only, low-noise).
 * Registered in the repo's webhooks (events: push, pull_request, workflow_run).
 *
 * Signature verification is enforced when GITHUB_WEBHOOK_SECRET is configured
 * (recommended); until then it does a light shape check so random noise can't
 * spam the channel. The endpoint only posts a notification — no side effects.
 */
require('../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');

$raw = file_get_contents('php://input');

// Verify the GitHub HMAC signature if a secret is set.
if (defined('GITHUB_WEBHOOK_SECRET') && GITHUB_WEBHOOK_SECRET !== '') {
    $sig = isset($_SERVER['HTTP_X_HUB_SIGNATURE_256']) ? $_SERVER['HTTP_X_HUB_SIGNATURE_256'] : '';
    $expected = 'sha256=' . hash_hmac('sha256', $raw, GITHUB_WEBHOOK_SECRET);
    if (!is_string($sig) || !hash_equals($expected, $sig)) {
        http_response_code(401);
        die('bad signature');
    }
}

$event = isset($_SERVER['HTTP_X_GITHUB_EVENT']) ? $_SERVER['HTTP_X_GITHUB_EVENT'] : '';
$req = json_decode($raw, true);

// Only a push to the prod branch (master) is a deployment.
if ($event === 'push' && is_array($req)
    && isset($req['ref']) && $req['ref'] === 'refs/heads/master'
    && isset($req['repository']['full_name']) && $req['repository']['full_name'] === 'untrobotics/website') {

    // Ignore branch deletes (no head_commit).
    if (!empty($req['deleted'])) {
        http_response_code(200);
        die('ok');
    }

    $head    = isset($req['head_commit']) && is_array($req['head_commit']) ? $req['head_commit'] : array();
    $sha     = isset($head['id']) ? substr($head['id'], 0, 7) : '';
    $subject = isset($head['message']) ? strtok($head['message'], "\n") : '';
    $pusher  = isset($req['pusher']['name']) ? $req['pusher']['name']
             : (isset($req['sender']['login']) ? $req['sender']['login'] : 'someone');
    $count   = isset($req['commits']) && is_array($req['commits']) ? count($req['commits']) : 0;
    $compare = isset($req['compare']) ? $req['compare'] : '';

    // Surface the deployed image tag if this push bumped it (kustomization).
    $version = '';
    if (isset($req['commits']) && is_array($req['commits'])) {
        foreach ($req['commits'] as $c) {
            if (isset($c['message']) && preg_match('/\bv\d+\.\d+\.\d+\b/', $c['message'], $m)) {
                $version = $m[0];
            }
        }
    }
    if ($version === '' && preg_match('/\bv\d+\.\d+\.\d+\b/', $subject, $m)) {
        $version = $m[0];
    }

    $msg = ":rocket: **Production deploy**"
         . ($version !== '' ? " — **{$version}**" : '')
         . "\n`master` → `{$sha}`" . ($subject !== '' ? " · {$subject}" : '')
         . "\n{$count} commit" . ($count === 1 ? '' : 's') . " by **{$pusher}**"
         . ($compare !== '' ? "\n<{$compare}>" : '');

    AdminBot::send_message(substr($msg, 0, 1900), DISCORD_WEBMASTERS_CHANNEL_ID);
}

// Pull requests: announce opened + merged.
if ($event === 'pull_request' && is_array($req) && isset($req['pull_request'])) {
    $pr     = $req['pull_request'];
    $action = isset($req['action']) ? $req['action'] : '';
    $num    = isset($pr['number']) ? $pr['number'] : '?';
    $title  = isset($pr['title']) ? $pr['title'] : '';
    $author = isset($pr['user']['login']) ? $pr['user']['login'] : 'someone';
    $url    = isset($pr['html_url']) ? $pr['html_url'] : '';
    $base   = isset($pr['base']['ref']) ? $pr['base']['ref'] : '';
    $head   = isset($pr['head']['ref']) ? $pr['head']['ref'] : '';

    $line = '';
    if ($action === 'opened' || $action === 'reopened') {
        $line = ":inbox_tray: **PR #{$num} opened** by **{$author}** — `{$head}` \u{2192} `{$base}`\n**{$title}**"
              . ($url !== '' ? "\n<{$url}>" : '');
    } elseif ($action === 'closed' && !empty($pr['merged'])) {
        $by = isset($pr['merged_by']['login']) ? $pr['merged_by']['login'] : $author;
        $line = ":twisted_rightwards_arrows: **PR #{$num} merged** into `{$base}` by **{$by}**\n**{$title}**"
              . ($url !== '' ? "\n<{$url}>" : '');
    }
    if ($line !== '') {
        AdminBot::send_message(substr($line, 0, 1900), DISCORD_WEBMASTERS_CHANNEL_ID);
    }
}

// CI failures only, on the main branches, so the channel isn't spammed by every run.
if ($event === 'workflow_run' && is_array($req) && isset($req['workflow_run'])) {
    $wr     = $req['workflow_run'];
    $action = isset($req['action']) ? $req['action'] : '';
    $branch = isset($wr['head_branch']) ? $wr['head_branch'] : '';
    if ($action === 'completed'
        && isset($wr['conclusion']) && $wr['conclusion'] === 'failure'
        && in_array($branch, array('master', 'develop'), true)) {
        $name = isset($wr['name']) ? $wr['name'] : 'workflow';
        $sha  = isset($wr['head_sha']) ? substr($wr['head_sha'], 0, 7) : '';
        $url  = isset($wr['html_url']) ? $wr['html_url'] : '';
        $line = ":x: **CI failed** \u{2014} {$name} on `{$branch}` (`{$sha}`)" . ($url !== '' ? "\n<{$url}>" : '');
        AdminBot::send_message(substr($line, 0, 1900), DISCORD_WEBMASTERS_CHANNEL_ID);
    }
}

http_response_code(200);
echo 'ok';
