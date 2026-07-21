<?php
/*
 * Newsletter drip sender (CLI).
 *
 * Sends the oldest 'sending' campaign to its queued recipients, capped so it never
 * pushes the day's Brevo total past DAILY_LIMIT - TRANSACTIONAL_RESERVE — i.e. it
 * always leaves the reserve as headroom for transactional mail (receipts, resets).
 * Whatever it can't send today it picks up on the next run, day after day, until
 * the campaign is done. Run it a few times a day from a CronJob.
 *
 * Usage: php cron/send-newsletter.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("cli only\n");
}

require_once(__DIR__ . '/../template/config.php');
require_once(BASE . '/template/top.php');

// Bound a single run so the job stays short even when the daily budget is large.
$PER_RUN_CAP = 100;

function out($m) { fwrite(STDOUT, '[' . date('c') . '] ' . $m . PHP_EOL); }

$remaining = min(brevo_newsletter_remaining_today(), $PER_RUN_CAP);
out("Daily newsletter budget left: " . brevo_newsletter_remaining_today() . " (sending up to {$remaining} this run)");
if ($remaining <= 0) {
    out('No budget left today; exiting.');
    exit(0);
}

// Oldest campaign still in flight.
$cq = $db->query("SELECT * FROM newsletter_campaigns WHERE status = 'sending' ORDER BY id ASC LIMIT 1");
if (!$cq || $cq->num_rows === 0) {
    out('No campaigns in "sending" state.');
    exit(0);
}
$campaign = $cq->fetch_assoc();
$cid = (int) $campaign['id'];
out("Campaign #{$cid}: {$campaign['subject']}");

// Grab a batch of pending recipients who have not unsubscribed.
$batch = $db->query("
    SELECT q.id, q.email
    FROM newsletter_queue q
    LEFT JOIN newsletter_signups s ON s.email = q.email
    WHERE q.campaign_id = {$cid}
      AND q.status = 'pending'
      AND (s.unsubscribed IS NULL OR s.unsubscribed = 0)
    ORDER BY q.id ASC
    LIMIT {$remaining}
");

$sent = 0;
if ($batch) {
    while ($row = $batch->fetch_assoc()) {
        $qid = (int) $row['id'];
        $addr = $row['email'];
        $body = $campaign['body'] . newsletter_unsub_footer($addr);
        // $archive=false: no BCC copy, so one send == one recipient == one budget unit.
        $ok = email($addr, $campaign['subject'], $body, false, null, array(), true, false);
        if ($ok) {
            $db->query("UPDATE newsletter_queue SET status='sent', sent_at=NOW(), attempts=attempts+1 WHERE id={$qid}");
            $db->query("UPDATE newsletter_campaigns SET sent_count = sent_count + 1 WHERE id={$cid}");
            $sent++;
        } else {
            // Leave it pending for a later run; after 3 tries give up on it.
            $db->query("UPDATE newsletter_queue SET attempts=attempts+1, error='send failed', status = IF(attempts+1 >= 3, 'failed', 'pending') WHERE id={$qid}");
            out("send failed for {$addr}");
        }
    }
}

// Any recipients left (excluding unsubscribed)? If none, the campaign is done.
$pend = (int) $db->query("
    SELECT COUNT(*) c
    FROM newsletter_queue q
    LEFT JOIN newsletter_signups s ON s.email = q.email
    WHERE q.campaign_id = {$cid} AND q.status = 'pending' AND (s.unsubscribed IS NULL OR s.unsubscribed = 0)
")->fetch_assoc()['c'];

out("Sent {$sent} this run; {$pend} still pending for campaign #{$cid}.");

if ($pend === 0) {
    $db->query("UPDATE newsletter_campaigns SET status='sent' WHERE id={$cid}");
    $total = (int) $db->query("SELECT sent_count FROM newsletter_campaigns WHERE id={$cid}")->fetch_assoc()['sent_count'];
    out("Campaign #{$cid} complete ({$total} sent).");
    if (class_exists('AdminBot')) {
        AdminBot::send_message("(Newsletter) Campaign #{$cid} \"{$campaign['subject']}\" finished — {$total} sent.");
    }
}
