<?php
/**
 * Warn in Discord (#webmasters) when a GitHub token is nearing expiry.
 *
 * Each run reads the token's CURRENT expiry live from the GitHub API response
 * header `github-authentication-token-expiration`, so there are NO hardcoded
 * dates — a renewed/rotated token is picked up automatically on the next run,
 * and the warning simply moves to the new expiry. Runs weekly (k8s CronJob).
 * A healthy token (expiry beyond the threshold) is silent; a token with no
 * expiry (classic PAT created without one) is skipped; a rejected token (401)
 * is reported as already expired/revoked.
 *
 * Tokens are identified by the env var that holds them, so this covers whatever
 * is in the web pod's env. GITHUB_DISPATCH_TOKEN = the fine-grained
 * DEVELOP_SYNC_PAT (CI releases + the Printful->mockup webhook dispatch). The
 * ghcr-pull image-pull PAT is a classic token with NO expiry and isn't in this
 * env, so it's intentionally not checked here.
 */
require(__DIR__ . '/../template/top.php');
require(BASE . '/api/discord/bots/admin.php');

$WARN_WITHIN_DAYS = 21;

// label => env var holding a GitHub token. Add entries as tokens gain expiries.
$tokens = array(
    'DEVELOP_SYNC_PAT (CI releases + webhook dispatch)' => 'GITHUB_DISPATCH_TOKEN',
);

// Tokens with NO queryable live expiry (e.g. Atlassian API tokens) — tracked by
// a hardcoded known expiry date. Unlike the GitHub tokens above, these are NOT
// self-updating: when you rotate the token you MUST update the date here, or the
// warning will keep firing (and eventually report it as expired). Format Y-m-d.
$fixed_expiry = array(
    'Jira API token "untrobotics-prod-jira-bot" (prod /jira command)' => '2027-09-10',
);

/**
 * Query GitHub with the token and return its current expiry info.
 *
 * @param string $token A GitHub PAT.
 * @return array ['status' => int HTTP code, 'expiry_raw' => string|null expiry header].
 */
function gh_token_expiry($token) {
    $exp = null;
    $ch = curl_init('https://api.github.com/rate_limit'); // cheap, doesn't count against the core limit
    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'User-Agent: untrobotics-token-check',
            'Accept: application/vnd.github+json',
        ),
        CURLOPT_HEADERFUNCTION => function ($ch, $header) use (&$exp) {
            if (stripos($header, 'github-authentication-token-expiration:') === 0) {
                $exp = trim(substr($header, strpos($header, ':') + 1));
            }
            return strlen($header);
        },
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 20,
    ));
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array('status' => $status, 'expiry_raw' => $exp);
}

$warnings = array();
foreach ($tokens as $label => $envvar) {
    $token = getenv($envvar);
    if (empty($token)) {
        error_log("[token-check] {$envvar} not set — skipping");
        continue;
    }
    $r = gh_token_expiry($token);

    if ($r['status'] === 401) {
        $warnings[] = ":rotating_light: **{$label}** — GitHub rejected it (HTTP 401): **expired or revoked**. Rotate it now.";
        continue;
    }
    if ($r['status'] < 200 || $r['status'] >= 300) {
        error_log("[token-check] {$label}: unexpected HTTP {$r['status']}");
        continue;
    }
    if (empty($r['expiry_raw'])) {
        error_log("[token-check] {$label}: no expiry set — skipping");
        continue;
    }

    $exp = strtotime($r['expiry_raw']);
    $days = (int) floor(($exp - time()) / 86400);
    error_log("[token-check] {$label}: expires in {$days} day(s) ({$r['expiry_raw']})");
    if ($days <= $WARN_WITHIN_DAYS) {
        $when = date('Y-m-d', $exp);
        $warnings[] = ":warning: **{$label}** expires in **{$days} day" . ($days === 1 ? '' : 's') . "** (on {$when}). "
            . "Rotate it, then update the GitHub Actions secret `DEVELOP_SYNC_PAT`, the prod `web-secrets` key `GITHUB_DISPATCH_TOKEN`, and the local `~/.gh-pat-untr-tok` / `.claude/tools/.github.json` — otherwise CI, releases, and the mockup webhook all break.";
    }
}

foreach ($fixed_expiry as $label => $date) {
    $exp = strtotime($date . ' 00:00:00 UTC');
    if ($exp === false) {
        error_log("[token-check] {$label}: bad fixed date '{$date}' — skipping");
        continue;
    }
    $days = (int) floor(($exp - time()) / 86400);
    error_log("[token-check] {$label}: expires in {$days} day(s) (on {$date})");
    if ($days > $WARN_WITHIN_DAYS) {
        continue;
    }
    if ($days < 0) {
        $warnings[] = ":rotating_light: **{$label}** — **expired " . abs($days) . " day" . (abs($days) === 1 ? '' : 's') . " ago** (on {$date}). "
            . "Re-mint at id.atlassian.com/manage-profile/security/api-tokens, update the prod `web-secrets` key `JIRA_API_TOKEN`, then bump the date in `cron/check-token-expiry.php`.";
    } else {
        $warnings[] = ":warning: **{$label}** expires in **{$days} day" . ($days === 1 ? '' : 's') . "** (on {$date}). "
            . "Re-mint at id.atlassian.com/manage-profile/security/api-tokens, update the prod `web-secrets` key `JIRA_API_TOKEN`, then bump the date in `cron/check-token-expiry.php` — otherwise `/jira` breaks.";
    }
}

if ($warnings) {
    AdminBot::send_message(
        substr(":key: **Token expiry check**\n" . implode("\n", $warnings), 0, 1900),
        DISCORD_WEBMASTERS_CHANNEL_ID
    );
    echo 'posted ' . count($warnings) . " warning(s) to #webmasters\n";
} else {
    echo "all checked tokens healthy — no warnings\n";
}
