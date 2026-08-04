<?php
/**
 * cron/unassign-expired-good-standing.php
 * ----------------------------------------
 * Removes the Discord "Good Standing" role (and its dependent team roles) from
 * members whose dues have lapsed for the CURRENT term, then DMs them.
 *
 * !!! THIS SCRIPT MUTATES ROLES AT SCALE — CHANGES ARE GATED, NOT DISABLED. !!!
 * It IS scheduled as the `unassign-expired-good-standing` K8s CronJob (monthly,
 * 1st @ 09:00 Central, invoked --apply --confirm). Real removals are still
 * limited to SAFE_MONTHS (Sept-April) by the season guard below, so off-season
 * firings run in analysis mode and change nothing. Run by hand with NO flags for
 * a read-only dry run. The original version would mass-strip the entire server
 * off-season and had no dry-run, rate-limit handling, or audit trail — all since
 * fixed (see SAFETY MODEL below).
 *
 * SAFETY MODEL
 *   1. CLI-only. Refuses to run under any web SAPI.
 *   2. Dry-run by default. Changes NOTHING unless invoked with --apply.
 *   3. Season guard. Even with --apply it refuses to mutate unless --confirm is
 *      passed AND the current month is inside SAFE_MONTHS (default Sept-April).
 *      This defends against get_term_from_date() flipping to AUTUMN in May, which
 *      would compute term=AUTUMN before anyone has paid and strip everyone.
 *   4. Duplicate / full-year payments (>= 1 dues row) count as paid.
 *   5. Unlinked Good-Standing holders (no users row) are SKIPPED, never stripped.
 *      Officers / alumni / bots in EXEMPT_DISCORD_IDS are SKIPPED, never stripped.
 *   6. All Discord calls are wrapped in try/catch with 429 retry handling, and the
 *      "dues expired" DM is only sent AFTER the role removal is confirmed.
 *   7. Every action is written to a persistent, timestamped audit log under /logs.
 *
 * SAFE INVOCATION
 *   Inspect first (safe, read-only, changes nothing):
 *       php cron/unassign-expired-good-standing.php
 *   Apply for real (only inside the safe-month window):
 *       php cron/unassign-expired-good-standing.php --apply --confirm
 *
 * Exit codes: 0 = ok / dry-run, 1 = environment/bootstrap error,
 *             2 = refused (guard not satisfied), 3 = Discord fetch failed.
 */

// -----------------------------------------------------------------------------
// 8. LOCKDOWN — CLI only. Never allow this to be triggered by an HTTP request.
// -----------------------------------------------------------------------------
if (PHP_SAPI !== 'cli' || !empty($_SERVER['REQUEST_METHOD']) || !empty($_SERVER['HTTP_HOST'])) {
	http_response_code(403);
	die("This script is command-line only and cannot be run over the web.\n");
}

// -----------------------------------------------------------------------------
// Bootstrap with absolute include paths. No session bootstrap (we do NOT pull in
// template/top.php, which would call session_start()); we just need the config
// constants, the untrobotics helper, and the Discord bot classes, plus our own
// short-lived DB handle.
// -----------------------------------------------------------------------------
require_once(__DIR__ . '/../template/config.php');          // defines BASE + DISCORD_* + DATABASE_* + TIMEZONE
require_once(BASE . '/template/classes/untrobotics.php');   // Semester + untrobotics
require_once(BASE . '/api/discord/bots/admin.php');         // DiscordBot + AdminBot

mysqli_report(MYSQLI_REPORT_OFF); // match the codebase's "if (!$q)" idiom (see template/top.php)
$db = new mysqli(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
if ($db->connect_errno) {
	fwrite(STDERR, 'Database connect error (' . $db->connect_errno . ') ' . $db->connect_error . PHP_EOL);
	exit(1);
}
$db->set_charset(DATABASE_CHARSET);
date_default_timezone_set(TIMEZONE);
$untrobotics = new untrobotics($db);

// -----------------------------------------------------------------------------
// Script-local configuration (intentionally NOT in template/config.php).
// -----------------------------------------------------------------------------

// 1. SEASON SAFETY GUARD: months (1-12) in which it is safe to APPLY removals.
//    Default: September(9) through April(4). Outside this window the run is
//    refused even with --apply --confirm, because the shared get_term_from_date()
//    reports AUTUMN from May onward (before the new term's dues exist).
const SAFE_MONTHS = [9, 10, 11, 12, 1, 2, 3, 4];

// 4. EXEMPTION ALLOW-LIST: Discord user IDs that must NEVER be stripped
//    (officers, alumni, service bots). Add officer/alumni IDs here as needed.
const EXEMPT_DISCORD_IDS = [
	'758538949810585641', // Admin Bot
	'765776425176399902', // Dev Admin Bot
];

// 5. Rate-limit / pacing tuning.
const RATE_LIMIT_MAX_RETRIES = 5; // max 429 retries per Discord call
const INTER_USER_SLEEP_SECONDS = 4; // pause between users when applying (matches original)

// The DM sent to a member after their Good Standing role is removed.
const EXPIRY_DM_MESSAGE =
	"Hello! This is the UNT Robotics administrative bot.\n" .
	"Your _Good Standing_ role has expired and has been removed from your account. If you had any other roles in the server which required _Good Standing_, those have been removed as well.\n" .
	"If you would like to regain the _Good Standing_ role and any other roles you had, please pay your dues for this semester at https://untro.bo/dues.\n" .
	"If you have any questions or concerns, or if your _Good Standing_ role should not have expired, please ask an officer in the #help-and-support channel (https://discord.gg/CvrRsqndEf).";

// -----------------------------------------------------------------------------
// 6. AUDIT LOG — persistent, timestamped. Wires up the old line-9 "TODO".
// -----------------------------------------------------------------------------
$LOG_DIR = BASE . '/logs';
if (!is_dir($LOG_DIR)) {
	@mkdir($LOG_DIR, 0775, true);
}
$LOG_FILE = $LOG_DIR . '/unassign-expired-good-standing-' . date('Y-m-d') . '.log';

/**
 * Write a line to the audit log and echo it to stdout. Never throws.
 */
function audit_log(string $message): void {
	global $LOG_FILE;
	$line = '[' . date('c') . '] ' . $message . PHP_EOL;
	echo $line;
	if ($LOG_FILE) {
		@file_put_contents($LOG_FILE, $line, FILE_APPEND | LOCK_EX);
	}
}

// -----------------------------------------------------------------------------
// CLI flags
// -----------------------------------------------------------------------------
$argv = $argv ?? [];
$apply   = in_array('--apply', $argv, true);
$confirm = in_array('--confirm', $argv, true);
$force   = in_array('--force', $argv, true); // deliberately override the season guard (intentional off-season mass strip)
if (in_array('--help', $argv, true) || in_array('-h', $argv, true)) {
	echo "Usage: php " . basename(__FILE__) . " [--apply] [--confirm]\n";
	echo "  (no flags)         Dry-run. Reports what WOULD happen. Changes nothing.\n";
	echo "  --apply --confirm  Actually remove roles + DM users. Only works inside the\n";
	echo "                     safe-month window (" . implode(',', SAFE_MONTHS) . ").\n";
	exit(0);
}

// -----------------------------------------------------------------------------
// Small helpers
// -----------------------------------------------------------------------------

/** True when a Discord REST response object reports a 2xx status. */
function discord_status_ok($response): bool {
	return $response instanceof stdClass
		&& isset($response->status_code)
		&& $response->status_code >= 200
		&& $response->status_code < 300;
}

/** Safe display name for a member's user object (8.3-safe; discriminator may be "0"). */
function discord_display_name($userObj): string {
	$username = (isset($userObj->username) && is_string($userObj->username)) ? $userObj->username : 'unknown';
	$disc = isset($userObj->discriminator) ? (string)$userObj->discriminator : '0';
	return ($disc !== '0' && $disc !== '') ? "{$username}#{$disc}" : $username;
}

/**
 * 5. Run a Discord REST call (one returning a response object with status_code)
 *    with try/catch + 429 retry handling. Returns the response object, or null
 *    on hard failure / exhausted retries.
 *
 *    NOTE: Discord's REST API returns retry_after in SECONDS (a float), not
 *    milliseconds. The original code divided by 1000, so it effectively never
 *    waited. This is the corrected math.
 */
function discord_call(callable $fn, string $label) {
	for ($attempt = 1; $attempt <= RATE_LIMIT_MAX_RETRIES; $attempt++) {
		try {
			$response = $fn();
		} catch (Throwable $e) {
			audit_log("ERROR  {$label}: " . $e->getMessage());
			return null;
		}

		if (DiscordBot::hasHitRateLimit($response)) {
			$retry_after = 1.0;
			if ($response instanceof stdClass && isset($response->result) && isset($response->result->retry_after)) {
				$retry_after = (float)$response->result->retry_after; // SECONDS
			}
			$wait = (int)ceil($retry_after) + 1;
			audit_log("RATE   {$label}: hit rate limit, waiting {$wait}s (retry_after={$retry_after}, attempt {$attempt})");
			sleep($wait);
			continue;
		}

		return $response;
	}

	audit_log("ERROR  {$label}: exhausted rate-limit retries");
	return null;
}

/**
 * 5. Open a DM channel with retry. AdminBot::create_dm() only returns the channel
 *    id (it discards the HTTP status), so we cannot read retry_after here; on an
 *    empty result we back off a fixed amount and retry. Returns channel id or null.
 */
function create_dm_with_retry(string $discord_id, string $label): ?string {
	for ($attempt = 1; $attempt <= RATE_LIMIT_MAX_RETRIES; $attempt++) {
		try {
			$channel_id = AdminBot::create_dm($discord_id);
		} catch (Throwable $e) {
			audit_log("ERROR  {$label}: create_dm threw: " . $e->getMessage());
			return null;
		}
		if (is_string($channel_id) && $channel_id !== '') {
			return $channel_id;
		}
		$wait = 2 * $attempt;
		audit_log("RETRY  {$label}: create_dm returned no channel id, waiting {$wait}s (attempt {$attempt})");
		sleep($wait);
	}
	return null;
}

/**
 * 3. Count dues payments for a user in the given term/year. Treat >= 1 as paid
 *    (a member who paid twice / for the full year has 2 rows and must NOT be
 *    stripped). We fetch the count directly here rather than calling
 *    untrobotics::is_user_in_good_standing(), which uses `num_rows === 1` and
 *    would wrongly treat a 2-row member as NOT in good standing.
 */
function count_dues_payments(mysqli $db, string $uid, int $term, string $year): int {
	$q = $db->query('
		SELECT COUNT(*) AS c FROM dues_payments
		WHERE
			uid = "' . $db->real_escape_string($uid) . '" AND
			dues_term = "' . $db->real_escape_string((string)$term) . '" AND
			dues_year = "' . $db->real_escape_string($year) . '"
	');
	if (!$q) {
		// Fail SAFE: if we can't read payments, assume paid so we never strip on a DB error.
		audit_log('ERROR  dues lookup failed for uid=' . $uid . ': ' . $db->error . ' (treating as PAID / in good standing)');
		return 1;
	}
	$row = $q->fetch_assoc();
	return (int)($row['c'] ?? 0);
}

// =============================================================================
// 1 + 2. Determine the term/year this run will check and decide the run mode.
// =============================================================================
$term = $untrobotics->get_current_term();          // int (Semester::*) from shared get_term_from_date()
$year = (string)$untrobotics->get_current_year();  // e.g. "2026"
$term_name = Semester::get_name_from_value($term) ?? ('#' . $term);
$current_month = (int)date('n');
$in_safe_window = in_array($current_month, SAFE_MONTHS, true);

audit_log('=== unassign-expired-good-standing start ===');
audit_log("Checking term: {$term_name} ({$term})  year: {$year}  (current month: {$current_month}, safe window: " . ($in_safe_window ? 'YES' : 'NO') . ')');

// =============================================================================
// 7. PAGINATION CAVEAT.
// AdminBot::get_all_users() calls GET /guilds/{id}/members?limit=1000 with NO
// pagination, so on a server with > 1000 members this only ever sees the first
// page and silently ignores everyone after them. Proper pagination requires an
// `after=<last_user_id>` loop, but DiscordBot::send_api_request() is `protected`
// and AdminBot::get_all_users() takes no `after` argument, so it cannot be done
// from this script without refactoring the bot classes (explicitly out of scope
// here). This is recorded as a residual risk; if the guild grows past 1000 the
// bot classes must gain a paginated members fetch before this script is trusted.
// =============================================================================
$users = discord_call(fn() => AdminBot::get_all_users(), 'get_all_users');
if (!($users instanceof stdClass) || !isset($users->result) || !is_array($users->result)) {
	audit_log('FATAL  Could not fetch guild members from Discord; aborting (no changes made).');
	exit(3);
}
$discord_users = $users->result;
audit_log('Fetched ' . count($discord_users) . ' member record(s) from Discord (cap 1000 — see pagination note).');

// =============================================================================
// Analysis pass (read-only): build the list of members who WOULD be stripped.
// =============================================================================
$to_process      = []; // [['member' => obj, 'uid' => string, 'dependent' => array]]
$skipped_exempt  = 0;
$skipped_unlinked = 0;
$skipped_paid    = 0;

foreach ($discord_users as $key => $val) {
	if (!($val instanceof stdClass) || !isset($val->user) || !isset($val->roles) || !is_array($val->roles)) {
		continue; // malformed member record — guard for PHP 8.3
	}
	if (!in_array(DISCORD_GOOD_STANDING_ROLE_ID, $val->roles, true)) {
		continue; // doesn't hold Good Standing — nothing to do
	}

	$discord_id = isset($val->user->id) ? (string)$val->user->id : '';
	$name = discord_display_name($val->user);

	// 4. Exemption allow-list: never strip officers / alumni / bots.
	if ($discord_id === '' || in_array($discord_id, EXEMPT_DISCORD_IDS, true)) {
		$skipped_exempt++;
		audit_log("SKIP   exempt/allow-listed: {$name} ({$discord_id})");
		continue;
	}

	// 4. Unlinked: a Good-Standing holder with no matching users row — never strip.
	$user = $untrobotics->get_user_by_discord_id($discord_id);
	if (!is_array($user) || !isset($user['id'])) {
		$skipped_unlinked++;
		audit_log("SKIP   no linked users row (will NOT strip): {$name} ({$discord_id})");
		continue;
	}
	$uid = (string)$user['id'];

	// 3. Duplicate / full-year payments: >= 1 row means paid.
	if (count_dues_payments($db, $uid, $term, $year) >= 1) {
		$skipped_paid++;
		continue; // in good standing — leave alone
	}

	// Dependent roles this member actually holds (only remove what they have).
	$dependent = array_values(array_intersect(DISCORD_GOOD_STANDING_DEPENDENT_ROLES, $val->roles));

	$to_process[] = ['member' => $val, 'uid' => $uid, 'dependent' => $dependent, 'name' => $name, 'discord_id' => $discord_id, 'key' => $key];
}

// 1. Report exactly what would be affected BEFORE doing anything.
$affected = count($to_process);
audit_log('---');
audit_log("WOULD AFFECT {$affected} member(s) for {$term_name} {$year}.");
audit_log("Skipped: paid/good-standing={$skipped_paid}, exempt={$skipped_exempt}, unlinked={$skipped_unlinked}.");
foreach ($to_process as $p) {
	audit_log("  -> {$p['key']}: {$p['name']} ({$p['discord_id']}) [uid {$p['uid']}] dependent_roles=" . count($p['dependent']));
}
audit_log('---');

// =============================================================================
// 1 + 2. Mode gate.
// =============================================================================
if (!$apply) {
	audit_log('MODE: DRY-RUN (default). No changes made. Re-run with --apply --confirm to apply.');
	foreach ($to_process as $p) {
		audit_log("DRYRUN would remove Good Standing + " . count($p['dependent']) . " dependent role(s) and DM {$p['name']} ({$p['discord_id']})");
	}
	audit_log('=== dry-run complete ===');
	exit(0);
}

// --apply was requested: enforce the season + confirm guard.
if (!$confirm || (!$in_safe_window && !$force)) {
	$reasons = [];
	if (!$confirm) {
		$reasons[] = 'missing --confirm flag';
	}
	if (!$in_safe_window) {
		$reasons[] = "current month ({$current_month}) is OUTSIDE the safe window [" . implode(',', SAFE_MONTHS) . "]";
	}
	audit_log('REFUSING TO APPLY: ' . implode('; ', $reasons) . '.');
	audit_log("This guard exists because get_term_from_date() reports {$term_name} from May onward, before new-term dues exist; applying off-season would strip the whole server.");
	audit_log('To apply during the correct season, run: php ' . basename(__FILE__) . ' --apply --confirm');
	exit(2);
}

// =============================================================================
// APPLY: actually remove roles and DM. Guarded by --apply --confirm + safe month.
// =============================================================================
audit_log("MODE: APPLY (confirmed, in safe window). Mutating {$affected} member(s).");

foreach ($to_process as $p) {
	$member     = $p['member'];
	$discord_id = $p['discord_id'];
	$name       = $p['name'];

	// Remove the Good Standing role first; only proceed once confirmed.
	$gs_result = discord_call(
		fn() => AdminBot::remove_user_role($discord_id, DISCORD_GOOD_STANDING_ROLE_ID),
		"remove good-standing role from {$name} ({$discord_id})"
	);
	if (!discord_status_ok($gs_result)) {
		audit_log("APPLY  action=remove_role role=good_standing user={$name} ({$discord_id}) result=FAILURE -- skipping dependent roles + DM");
		sleep(INTER_USER_SLEEP_SECONDS);
		continue;
	}
	audit_log("APPLY  action=remove_role role=good_standing user={$name} ({$discord_id}) result=success");

	// Remove dependent team roles the member actually held.
	foreach ($p['dependent'] as $role) {
		$r = discord_call(
			fn() => AdminBot::remove_user_role($discord_id, $role),
			"remove dependent role {$role} from {$name} ({$discord_id})"
		);
		$ok = discord_status_ok($r) ? 'success' : 'FAILURE';
		audit_log("APPLY  action=remove_role role={$role} user={$name} ({$discord_id}) result={$ok}");
	}

	// 5. Send the "dues expired" DM only AFTER the role removal is confirmed.
	$dm_channel = create_dm_with_retry($discord_id, "open DM with {$name} ({$discord_id})");
	if ($dm_channel === null) {
		audit_log("APPLY  action=dm user={$name} ({$discord_id}) result=FAILURE (could not open DM channel)");
	} else {
		$send = discord_call(
			fn() => AdminBot::send_message(EXPIRY_DM_MESSAGE, $dm_channel),
			"DM {$name} ({$discord_id})"
		);
		$ok = discord_status_ok($send) ? 'success' : 'FAILURE';
		audit_log("APPLY  action=dm user={$name} ({$discord_id}) result={$ok}");
	}

	sleep(INTER_USER_SLEEP_SECONDS);
}

// Note: the original file also contained a commented-out second "rogues" pass to
// catch members holding dependent roles without Good Standing. It is intentionally
// NOT implemented here; the primary loop above already strips dependent roles when
// it strips Good Standing, and a standalone rogue sweep needs its own review.

audit_log("=== apply complete: processed {$affected} member(s) ===");
exit(0);
