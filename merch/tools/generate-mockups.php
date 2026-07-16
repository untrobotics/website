<?php
/*
 * Render garment mockups for a synced Printful product and cache them locally.
 *
 * Printful's store API only returns one wearable "preview" per variant; the
 * other files are flat print artwork. This asks the Mockup Generator to render
 * the design on the garment for every angle it offers (back, left, right, ...),
 * downloads the results into images/merch/generated/, and records them in
 * mockups.json. merch/product.php reads that file and appends the angles to the
 * gallery next to Printful's front preview.
 *
 * Usage:  php merch/tools/generate-mockups.php <store_product_id> [more ids...]
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("cli only\n");
}

require_once(__DIR__ . '/../../template/config.php');

$out_dir = __DIR__ . '/../../images/merch/generated';
$json_path = $out_dir . '/mockups.json';
if (!is_dir($out_dir) && !mkdir($out_dir, 0775, true)) {
    exit("cannot create $out_dir\n");
}

$api_key = PRINTFUL_API_KEY;
if (empty($api_key)) {
    exit("PRINTFUL_API_KEY is not set\n");
}

// sync file type -> printfile placement key
$placement_alias = array('default' => 'front');

function printful($key, $method, $path, $body = null) {
    $ch = curl_init('https://api.printful.com/' . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $headers = array('Authorization: Bearer ' . $key);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        $headers[] = 'Content-Type: application/json';
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array($code, json_decode($raw, true));
}

function slug($s) {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

// reduce Printful's angle labels ("Back", "Right Back", "sleeve_left", ...) to a
// canonical view. front-facing angles return null since the preview covers them.
function canon_view($label) {
    $l = strtolower(str_replace('_', ' ', $label));
    $back = strpos($l, 'back') !== false;
    $front = strpos($l, 'front') !== false;
    $left = strpos($l, 'left') !== false;
    $right = strpos($l, 'right') !== false;
    if ($front && !$back) {
        return null;
    }
    if ($back && $left) {
        return 'back-left';
    }
    if ($back && $right) {
        return 'back-right';
    }
    if ($back) {
        return 'back';
    }
    if ($left) {
        return 'left';
    }
    if ($right) {
        return 'right';
    }
    return null;
}

// colour + size out of "<product> / <colour> / <size>" (or "<product> / <size>")
function split_variant($product_name, $variant_name) {
    $label = trim(str_ireplace($product_name, '', $variant_name), " /-\t");
    $parts = array_map('trim', explode(' / ', $label));
    if (count($parts) >= 2) {
        $size = array_pop($parts);
        return array(implode(' / ', $parts), $size);
    }
    return array('', $label);
}

$map = array();
if (is_readable($json_path)) {
    $existing = json_decode(file_get_contents($json_path), true);
    if (is_array($existing)) {
        $map = $existing;
    }
}

$ids = array_slice($argv, 1);
if (!$ids) {
    exit("usage: php generate-mockups.php <store_product_id> [more ids...]\n");
}

foreach ($ids as $store_id) {
    list($code, $data) = printful($api_key, 'GET', "store/products/$store_id");
    if ($code !== 200 || empty($data['result']['sync_variants'])) {
        echo "[$store_id] fetch failed (HTTP $code)\n";
        continue;
    }
    $result = $data['result'];
    $external_id = $result['sync_product']['external_id'];
    $product_name = $result['sync_variants'][0]['product']['name'];
    $catalog_product = $result['sync_variants'][0]['product']['product_id'];
    $store_name = $result['sync_product']['name'];
    echo "[$store_name] external=$external_id catalog=$catalog_product\n";

    // all-over garments print onto the sleeves, so their side angles show the
    // design; back-only designs leave the sides blank, so keep only the back.
    $all_over = false;
    foreach ($result['sync_variants'][0]['files'] as $f) {
        if (strpos($f['type'], 'sleeve') !== false) {
            $all_over = true;
            break;
        }
    }

    // one representative variant per colour
    $by_colour = array();
    $colour_of = array();
    foreach ($result['sync_variants'] as $sv) {
        list($colour) = split_variant($store_name, $sv['name']);
        if (!isset($by_colour[$colour])) {
            $by_colour[$colour] = $sv;
            $colour_of[$sv['product']['variant_id']] = $colour;
        }
    }

    // print-area size per placement
    $printfiles = null;
    foreach (array('vertical', 'horizontal') as $orientation) {
        list($pc, $pf) = printful($api_key, 'GET', "mockup-generator/printfiles/$catalog_product?orientation=$orientation");
        if ($pc === 200) {
            $printfiles = $pf['result'];
            break;
        }
    }
    if (!$printfiles) {
        echo "  no printfiles\n";
        continue;
    }
    $area = array();
    foreach ($printfiles['printfiles'] as $p) {
        $area[$p['printfile_id']] = array($p['width'], $p['height']);
    }

    foreach ($by_colour as $colour => $sv) {
        $variant_id = $sv['product']['variant_id'];
        $placement_file = array();
        foreach ($printfiles['variant_printfiles'] as $vp) {
            if ($vp['variant_id'] == $variant_id) {
                $placement_file = $vp['placements'];
                break;
            }
        }

        $files = array();
        foreach ($sv['files'] as $f) {
            if ($f['type'] === 'preview' || empty($f['preview_url'])) {
                continue;
            }
            $placement = isset($placement_alias[$f['type']]) ? $placement_alias[$f['type']] : $f['type'];
            if (!isset($placement_file[$placement]) || !isset($area[$placement_file[$placement]])) {
                continue;
            }
            list($aw, $ah) = $area[$placement_file[$placement]];
            $files[] = array(
                'placement' => $placement,
                'image_url' => $f['preview_url'],
                'position' => array('area_width' => $aw, 'area_height' => $ah, 'width' => $aw, 'height' => $ah, 'top' => 0, 'left' => 0),
            );
        }
        if (!$files) {
            echo "  [$colour] no printable placements\n";
            continue;
        }

        list($tc, $task) = printful($api_key, 'POST', "mockup-generator/create-task/$catalog_product", array(
            'variant_ids' => array($variant_id),
            'format' => 'jpg',
            'files' => $files,
        ));
        if ($tc !== 200) {
            echo "  [$colour] create-task HTTP $tc: " . json_encode($task) . "\n";
            continue;
        }
        $task_key = $task['result']['task_key'];

        $mockups = null;
        for ($i = 0; $i < 30; $i++) {
            sleep(5);
            list($sc, $status) = printful($api_key, 'GET', 'mockup-generator/task?task_key=' . urlencode($task_key));
            $state = isset($status['result']['status']) ? $status['result']['status'] : '?';
            if ($state === 'completed') {
                $mockups = $status['result']['mockups'];
                break;
            }
            if ($state === 'failed') {
                echo "  [$colour] task failed: " . json_encode($status['result']) . "\n";
                break;
            }
        }
        if (!$mockups) {
            continue;
        }

        // one image per canonical angle (deduped); front is dropped
        $views = array();
        foreach ($mockups as $mk) {
            $candidates = array(array($mk['placement'], $mk['mockup_url']));
            if (!empty($mk['extra'])) {
                foreach ($mk['extra'] as $ex) {
                    $candidates[] = array(isset($ex['title']) ? $ex['title'] : $ex['option'], $ex['url']);
                }
            }
            foreach ($candidates as $c) {
                list($label, $url) = $c;
                $view = canon_view($label);
                if ($view === null || (!$all_over && $view !== 'back')) {
                    continue;
                }
                if (!isset($views[$view])) {
                    $views[$view] = $url;
                }
            }
        }

        $entry = array();
        foreach ($views as $view => $url) {
            $file = slug($store_name) . '-' . ($colour !== '' ? slug($colour) : 'default') . '-' . $view . '.jpg';
            $bytes = @file_get_contents($url);
            if ($bytes === false) {
                echo "  [$colour] download failed: $view\n";
                continue;
            }
            file_put_contents($out_dir . '/' . $file, $bytes);
            $entry[] = array('view' => $view, 'path' => '/images/merch/generated/' . $file);
        }
        $n = count($entry);
        if ($entry) {
            $map[$external_id][$colour] = $entry;
        }
        echo "  [" . ($colour === '' ? 'one design' : $colour) . "] $n angle(s)\n";
    }
}

file_put_contents($json_path, json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "wrote " . $json_path . "\n";
