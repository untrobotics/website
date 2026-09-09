<?php
/*
 * Orchestrates mockup regeneration across ALL synced Printful products, so it
 * can run unattended (the scheduled/-event-triggered refresh workflow, see
 * .github/workflows/refresh-mockups.yml).
 *
 * For each store product it computes a signature from the design inputs (the
 * sync-variant file preview_urls that feed the Mockup Generator). It regenerates
 * a product only when that signature changed or the product has no mockups yet —
 * by shelling out to generate-mockups.php, which does the actual rendering. The
 * signatures are stored in images/merch/generated/signatures.json.
 *
 * The image bytes themselves are the real change gate: even if a signature
 * falsely trips, an identical re-render produces no git diff, so the workflow
 * commits (and redeploys) only genuine changes.
 *
 * Usage:
 *   php merch/tools/refresh-mockups.php          # regenerate new/changed only
 *   php merch/tools/refresh-mockups.php --all     # regenerate everything (monthly)
 *   php merch/tools/refresh-mockups.php --seed     # only (re)write signatures, no render
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("cli only\n");
}

$config = __DIR__ . '/../../template/config.php';
if (is_readable($config)) {
    require_once($config);
}

$api_key = defined('PRINTFUL_API_KEY') && PRINTFUL_API_KEY !== '' ? PRINTFUL_API_KEY : getenv('PRINTFUL_API_KEY');
if (empty($api_key)) {
    exit("PRINTFUL_API_KEY is not set\n");
}

$args = array_slice($argv, 1);
$do_all  = in_array('--all', $args, true);
$do_seed = in_array('--seed', $args, true);

$out_dir   = __DIR__ . '/../../images/merch/generated';
$mock_path = $out_dir . '/mockups.json';
$sig_path  = $out_dir . '/signatures.json';
if (!is_dir($out_dir) && !mkdir($out_dir, 0775, true)) {
    exit("cannot create $out_dir\n");
}

function pf_get($key, $path) {
    $ch = curl_init('https://api.printful.com/' . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $key));
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($code, json_decode($raw, true));
}

/**
 * A stable fingerprint of the inputs that determine a product's mockups: the
 * set of (placement type, preview-file path) across its sync variants. Query
 * strings are stripped so a re-signed CDN URL doesn't look like a design change.
 */
function product_signature($detail) {
    $parts = array();
    foreach ($detail['result']['sync_variants'] as $sv) {
        foreach ($sv['files'] as $f) {
            if (empty($f['preview_url']) || $f['type'] === 'preview') {
                continue;
            }
            $url = strtok($f['preview_url'], '?');
            $parts[] = $f['type'] . '=' . $url;
        }
    }
    $parts = array_values(array_unique($parts));
    sort($parts);
    return md5(implode('|', $parts));
}

$signatures = array();
if (is_readable($sig_path)) {
    $j = json_decode(file_get_contents($sig_path), true);
    if (is_array($j)) { $signatures = $j; }
}
$have_mockups = array();
if (is_readable($mock_path)) {
    $j = json_decode(file_get_contents($mock_path), true);
    if (is_array($j)) { $have_mockups = $j; }
}

// Enumerate all store products.
list($code, $list) = pf_get($api_key, 'store/products?limit=100');
if ($code !== 200 || !isset($list['result'])) {
    fwrite(STDERR, "failed to list store products (HTTP $code)\n");
    exit(1);
}

$to_render = array();     // store_id => reason
$new_signatures = $signatures;

foreach ($list['result'] as $p) {
    $store_id = $p['id'];
    $external_id = $p['external_id'];
    list($dc, $detail) = pf_get($api_key, "store/products/$store_id");
    if ($dc !== 200 || empty($detail['result']['sync_variants'])) {
        echo "skip $store_id ({$p['name']}): detail HTTP $dc\n";
        continue;
    }
    $sig = product_signature($detail);
    $new_signatures[$external_id] = $sig;

    // "new"  = never signed before (a product added since the baseline);
    // "changed" = its design signature moved.
    // --all (monthly full) refreshes only products that ALREADY have mockups, so
    // it re-renders the curated set in case Printful changed a render — without
    // mass-creating mockups for every catalogue item that never had them.
    $reason = null;
    if ($do_all) {
        if (isset($have_mockups[$external_id])) { $reason = 'full'; }
    } elseif (!isset($signatures[$external_id])) {
        $reason = 'new';
    } elseif ($signatures[$external_id] !== $sig) {
        $reason = 'changed';
    }
    if ($reason && !$do_seed) {
        $to_render[$store_id] = $reason;
    }
    echo sprintf("%-12s %-40s %s\n", $store_id, substr($p['name'], 0, 40), $reason ? "-> $reason" : 'up to date');
}

if ($do_seed) {
    file_put_contents($sig_path, json_encode($new_signatures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "seeded " . count($new_signatures) . " signature(s); no rendering.\n";
    exit(0);
}

if (!$to_render) {
    // Still persist signatures (e.g. first run) so subsequent runs are diff-only.
    file_put_contents($sig_path, json_encode($new_signatures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "nothing to render.\n";
    exit(0);
}

// Render the new/changed products via the existing generator (it merges into
// mockups.json and downloads the images).
$ids = array_map('strval', array_keys($to_render));
echo "\nrendering " . count($ids) . " product(s): " . implode(', ', $ids) . "\n";
$cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/generate-mockups.php');
foreach ($ids as $id) { $cmd .= ' ' . escapeshellarg($id); }
passthru($cmd, $rc);

// Only record the new signatures once rendering succeeded, so a failed run
// re-attempts the same products next time instead of marking them done.
if ($rc === 0) {
    file_put_contents($sig_path, json_encode($new_signatures, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "updated signatures.\n";
} else {
    fwrite(STDERR, "generate-mockups.php exited $rc; signatures not updated.\n");
    exit($rc);
}
