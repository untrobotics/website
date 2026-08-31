<?php
/**
 * Merch catalogue metadata shared by the category listings, the Printful product
 * page, and the Amazon product page.
 *
 * Category is normally driven by a "(Token)" suffix on the Printful product name
 * (e.g. "UNT Robotics Beanie (Hats)"). MERCH_CATEGORY_OVERRIDES fixes a few
 * legacy products whose Printful name still carries the wrong suffix, without
 * having to rename them in Printful.
 */

// Canonical category token => [url slug, display name]. The token is what appears
// in "(...)" at the end of a Printful product name; the slug is the /merch/<slug>
// page. Keeping these in one place lets the product page link its breadcrumb back
// to the right category even for tokens (like "Shirts & Hoodies") whose display
// name isn't a clean URL.
const MERCH_CATEGORIES = array(
    'Shirts & Hoodies' => array('shirts-hoodies', 'Shirts & Hoodies'),
    'Hats'             => array('hats', 'Hats'),
    'Trousers'         => array('trousers', 'Trousers'),
    'Gear'             => array('gear', 'Gear'),
);

// Legacy Printful products whose name still ends in the wrong "(...)" suffix.
// Matched as a case-insensitive substring of the product name; first match wins.
// "Bomber Jacket" catches both the plain and the "with Back Text" jackets.
const MERCH_CATEGORY_OVERRIDES = array(
    'Aerospace Division T' => 'Shirts & Hoodies',
    'Bomber Jacket'        => 'Shirts & Hoodies',
);

/**
 * Normalise a category token for tolerant matching: lower-case, strip anything
 * non-alphanumeric, then drop a trailing "s". Lets the Printful suffix "(Hat)"
 * match the canonical "Hats", and "(Shirts & Hoodies)" match "Shirts & Hoodies".
 */
function merch_norm_category($s) {
    $s = strtolower(preg_replace('@[^a-z0-9]@i', '', (string) $s));
    return preg_replace('@s$@', '', $s);
}

/**
 * Resolve a product's canonical category token (a MERCH_CATEGORIES key) from its
 * (Printful) name. Overrides win; otherwise the trailing "(Token)" is read and
 * matched against the known tokens tolerant of singular/plural and punctuation.
 * Returns null when there's no recognisable category.
 */
function merch_resolve_category($name) {
    foreach (MERCH_CATEGORY_OVERRIDES as $needle => $token) {
        if (stripos($name, $needle) !== false) {
            return $token;
        }
    }
    if (preg_match('@\(([^()]+)\)\s*$@', $name, $m)) {
        $norm = merch_norm_category($m[1]);
        foreach (array_keys(MERCH_CATEGORIES) as $known) {
            if (merch_norm_category($known) === $norm) {
                return $known;
            }
        }
        return trim($m[1]);
    }
    return null;
}

/** URL slug for a category token (falls back to a slugified token). */
function merch_category_slug($token) {
    if (isset(MERCH_CATEGORIES[$token])) {
        return MERCH_CATEGORIES[$token][0];
    }
    return strtolower(preg_replace('@[^a-z0-9]+@i', '-', trim($token)));
}

/** Human display name for a category token. */
function merch_category_display($token) {
    if (isset(MERCH_CATEGORIES[$token])) {
        return MERCH_CATEGORIES[$token][1];
    }
    return $token;
}

/**
 * Amazon-fulfilled merch. These aren't in Printful and can't be checked out on
 * the site, so each has its own product page (/merch/amazon/<slug>) with a
 * "Buy on Amazon" button. `images` is [front, back]; `back` text may be '' for
 * front-only designs.
 */
function merch_amazon_products() {
    static $products = null;
    if ($products !== null) {
        return $products;
    }
    $products = array(
        array(
            'slug' => 'premium-tshirt', 'name' => 'Premium T-shirt', 'category' => 'Shirts & Hoodies',
            'price' => 21.00, 'front' => 'Green Engineering Eagle Logo', 'back' => 'Large white UNT Robotics Logo',
            'asin' => 'B08HRYRXZH', 'amazon_url' => 'https://www.amazon.com/dp/B08HRYRXZH',
            'images' => array(
                'https://m.media-amazon.com/images/I/91IM87eeuCL._AC_CLa%7C2140%2C2000%7C51EkzFK%2BZ9L.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
                'https://m.media-amazon.com/images/I/91TEt6lN0LL._AC_CLa%7C2140%2C2000%7C710mMTLKfpL.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
            ),
        ),
        array(
            'slug' => 'standard-tshirt', 'name' => 'Standard T-Shirt', 'category' => 'Shirts & Hoodies',
            'price' => 20.00, 'front' => 'White Engineering Eagle Logo', 'back' => 'Large white UNT Robotics Logo',
            'asin' => 'B08HTK372G', 'amazon_url' => 'https://www.amazon.com/dp/B08HTK372G',
            'images' => array(
                'https://m.media-amazon.com/images/I/A13usaonutL._AC_CLa%7C2140%2C2000%7C41umV2TbUBL.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
                'https://m.media-amazon.com/images/I/A1ehn8ON9oL._AC_CLa%7C2140%2C2000%7C71qf4IsFlHL.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
            ),
        ),
        array(
            'slug' => 'standard-tshirt-front-only', 'name' => 'Standard T-Shirt', 'category' => 'Shirts & Hoodies',
            'price' => 14.00, 'front' => 'White Engineering Eagle Logo', 'back' => '',
            'asin' => 'B08J5KWRRP', 'amazon_url' => 'https://www.amazon.com/dp/B08J5KWRRP',
            'images' => array(
                'https://m.media-amazon.com/images/I/B1SqOvJ6PXS._AC_CLa%7C2140%2C2000%7C41XC8Rb-7BL.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
                '/images/merch/amazon/unt-robotics-shirt-green-standard-black.png',
            ),
        ),
        array(
            'slug' => 'pullover-hoodie', 'name' => 'Pullover Hoodie', 'category' => 'Shirts & Hoodies',
            'price' => 35.00, 'front' => 'Green Engineering Eagle Logo', 'back' => 'Large white UNT Robotics Logo',
            'asin' => 'B08HT3FNBW', 'amazon_url' => 'https://www.amazon.com/dp/B08HT3FNBW',
            'images' => array(
                'https://m.media-amazon.com/images/I/B1i3u9-Q-KS._AC_CLa%7C2140%2C2000%7CB1zd1paW78S.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
                'https://m.media-amazon.com/images/I/A1y6JwyATPL._AC_CLa%7C2140%2C2000%7CB1cJgk-zvAS.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
            ),
        ),
        array(
            'slug' => 'pullover-hoodie-front-only', 'name' => 'Pullover Hoodie', 'category' => 'Shirts & Hoodies',
            'price' => 30.00, 'front' => 'Green Engineering Eagle Logo', 'back' => '',
            'asin' => 'B08J63R8MQ', 'amazon_url' => 'https://www.amazon.com/dp/B08J63R8MQ?customId=B078RWY6LM&th=1',
            'images' => array(
                'https://m.media-amazon.com/images/I/B1Wsm-8LxOS._AC_CLa%7C2140%2C2000%7CB1az-aC2-hS.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_SX679._SX._UX._SY._UY_.png',
                '/images/merch/amazon/unt-robotics-pullover-basic-green-grey.png',
            ),
        ),
        array(
            'slug' => 'zip-hoodie-front-only', 'name' => 'Zip Hoodie', 'category' => 'Shirts & Hoodies',
            'price' => 30.00, 'front' => 'White Engineering Eagle Logo', 'back' => '',
            'asin' => 'B08J1HJKDF', 'amazon_url' => 'https://www.amazon.com/dp/B08J1HJKDF',
            'images' => array(
                'https://m.media-amazon.com/images/I/C1KYWScWstS._AC_CLa%7C2140%2C2000%7C416yXpyIW2L.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
                '/images/merch/amazon/unt-robotics-zip-basic-green-back.png',
            ),
        ),
        array(
            'slug' => 'zip-hoodie', 'name' => 'Zip Hoodie', 'category' => 'Shirts & Hoodies',
            'price' => 35.00, 'front' => 'Green Engineering Eagle Logo', 'back' => 'Large white UNT Robotics Logo',
            'asin' => 'B08HWPZPDK', 'amazon_url' => 'https://www.amazon.com/dp/B08HWPZPDK',
            'images' => array(
                'https://m.media-amazon.com/images/I/B1FGy+bPeZS._AC_CLa%7C2140%2C2000%7C51J8VeHIPyL.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
                'https://m.media-amazon.com/images/I/B14zf5cQ1wS._AC_CLa%7C2140%2C2000%7C71YSWxZ2GhL.png%7C0%2C0%2C2140%2C2000%2B0.0%2C0.0%2C2140.0%2C2000.0_UX679_.png',
            ),
        ),
    );
    return $products;
}

/** Amazon products in a given category token. */
function merch_amazon_by_category($token) {
    $out = array();
    foreach (merch_amazon_products() as $p) {
        if ($p['category'] === $token) {
            $out[] = $p;
        }
    }
    return $out;
}

/** Look up a single Amazon product by its slug. */
function merch_amazon_by_slug($slug) {
    foreach (merch_amazon_products() as $p) {
        if ($p['slug'] === $slug) {
            return $p;
        }
    }
    return null;
}
