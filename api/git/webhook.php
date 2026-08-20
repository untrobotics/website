<?php
/**
 * GitHub webhook -> Discord. Posts a deploy summary to #webmasters whenever
 * master advances, because prod tracks master (we promote develop -> master and
 * sync Argo to ship). Registered in the repo's webhooks (event: push).
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

http_response_code(200);
echo 'ok';
