<?php
/**
 * Lightweight forward-only DB migration runner.
 *
 * Tracks applied migrations in the `schema_migrations` table and applies every
 * file in sql/migrations/*.sql that has not yet been recorded, in ascending
 * filename order. Safe to run repeatedly — already-applied files are skipped.
 *
 * Usage (from repo root or anywhere):
 *   php sql/migrate.php            Apply all pending migrations.
 *   php sql/migrate.php status     List applied vs pending, then exit.
 *   php sql/migrate.php baseline   Record ALL current files as applied WITHOUT
 *                                  running them. Use this ONCE on an existing
 *                                  database that is already at the current
 *                                  schema (e.g. after adopting this tool), so
 *                                  historical migrations are not re-run.
 *
 * Convention for NEW migration files: prefix with a sortable timestamp so they
 * apply in chronological order, e.g. 20260707-add-widget-table.sql
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("migrate.php is a CLI tool.\n");
}

require_once(__DIR__ . '/../template/top.php');
global $db;

$mode = isset($argv[1]) ? $argv[1] : 'migrate';

$db->query("CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `filename` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$applied = array();
if ($res = $db->query("SELECT filename FROM schema_migrations ORDER BY filename")) {
    while ($row = $res->fetch_row()) { $applied[$row[0]] = true; }
}

$files = glob(__DIR__ . '/migrations/*.sql');
sort($files, SORT_STRING);

function record($db, $name) {
    $stmt = $db->prepare("INSERT INTO `schema_migrations` (`filename`) VALUES (?)");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $stmt->close();
}

if ($mode === 'status') {
    fwrite(STDOUT, "Applied (" . count($applied) . "):\n");
    foreach (array_keys($applied) as $n) { fwrite(STDOUT, "  ✓ {$n}\n"); }
    $pending = array();
    foreach ($files as $f) { if (!isset($applied[basename($f)])) { $pending[] = basename($f); } }
    fwrite(STDOUT, "Pending (" . count($pending) . "):\n");
    foreach ($pending as $n) { fwrite(STDOUT, "  … {$n}\n"); }
    exit(0);
}

if ($mode === 'baseline') {
    $n = 0;
    foreach ($files as $file) {
        $name = basename($file);
        if (isset($applied[$name])) { continue; }
        record($db, $name);
        fwrite(STDOUT, "baselined {$name}\n");
        $n++;
    }
    fwrite(STDOUT, "Baseline complete. Marked {$n} file(s) as applied (not executed).\n");
    exit(0);
}

// mode: migrate
$ran = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (isset($applied[$name])) { continue; }
    $sql = file_get_contents($file);
    fwrite(STDOUT, "applying {$name} ... ");
    if (!$db->multi_query($sql)) {
        fwrite(STDERR, "FAILED: {$db->error}\n");
        exit(1);
    }
    do {
        if ($db->errno) { fwrite(STDERR, "FAILED: {$db->error}\n"); exit(1); }
    } while ($db->more_results() && $db->next_result());
    record($db, $name);
    fwrite(STDOUT, "ok\n");
    $ran++;
}
fwrite(STDOUT, "Done. Applied {$ran} new migration(s); " . count($applied) . " already recorded.\n");
