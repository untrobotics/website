<?php
require(BASE . '/api/printful/printful.php');
require_once(BASE . '/merch/includes/merch-data.php');

// Listing thumbnail: Printful's product-level thumbnail_url follows whatever
// variant it lists first (often an off-brand colour like blue). Prefer a
// brand-green variant's garment mockup instead so the category page matches the
// product page's green default. Takes the already-fetched product (so the
// listing only pulls each product once) and falls back to Printful's thumbnail.
function merch_listing_image($product, $item) {
    if ($product) {
        $preferred = array('kelly', 'green', 'forest', 'irish', 'military');
        $first_preview = null;
        foreach ($product->get_variants() as $variant) {
            $pf = $variant->get_file_by_type(PrintfulVariantFilesTypes::PREVIEW);
            if (!$pf || !$pf->get_preview_url()) {
                continue;
            }
            if ($first_preview === null) {
                $first_preview = $pf->get_preview_url();
            }
            $vname = strtolower($variant->get_name());
            foreach ($preferred as $pc) {
                if (strpos($vname, $pc) !== false) {
                    return $pf->get_preview_url();
                }
            }
        }
        if ($first_preview !== null) {
            return $first_preview;
        }
    }
    return $item->thumbnail_url;
}

/**
 * Build (and cache for an hour) the full Printful catalogue as flat rows, each
 * tagged with its resolved category token. One fetch serves every category page.
 * Rows: name, external_id, price, thumb, category.
 */
function merch_printful_catalog() {
    $cache = sys_get_temp_dir() . '/merch-catalog-all.json';
    if (is_file($cache) && (time() - filemtime($cache)) < 3600) {
        $rows = json_decode(file_get_contents($cache), true);
        if (is_array($rows)) {
            return $rows;
        }
    }
    $rows = array();
    $printfulapi = new PrintfulCustomAPI();
    $items = $printfulapi->get_products('');
    foreach ($items->get_results() as $item) {
        $product = null;
        try { $product = $printfulapi->get_product('@' . $item->external_id); } catch (Exception $e) {}
        if ($product) {
            $price = $product->get_product_price();
            $name = $product->get_name();
        } else {
            $pp = $printfulapi->get_product_price($item->id);
            $price = $pp[0];
            $name = $item->name;
        }
        $rows[] = array(
            'name' => $name,
            'external_id' => $item->external_id,
            'price' => $price,
            'thumb' => merch_listing_image($product, $item),
            'category' => merch_resolve_category($name),
        );
    }
    @file_put_contents($cache, json_encode($rows));
    return $rows;
}

/**
 * Render a category listing for a category token (e.g. "Gear", "Shirts &
 * Hoodies"). Printful products in that category and any Amazon products in it are
 * meshed into one grid; each card is badged by where it's fulfilled.
 */
function merch_template($token) {
    $display = merch_category_display($token);
    $slug = merch_category_slug($token);

    // Assemble the combined card list: Printful (checkout on-site) + Amazon (Prime).
    $cards = array();
    foreach (merch_printful_catalog() as $row) {
        if ($row['category'] !== $token) {
            continue;
        }
        $display_name = trim(preg_replace('@\([^()]*\)\s*$@', '', $row['name']));
        $cards[] = array(
            'name' => $display_name,
            'price' => $row['price'],
            'thumb' => $row['thumb'],
            'url' => '/merch/product/' . $row['external_id'] . '/' . post_slug($row['name']),
            'badge' => 'onsite',
            'badge_label' => 'On-site',
        );
    }
    foreach (merch_amazon_by_category($token) as $p) {
        $cards[] = array(
            'name' => $p['name'],
            'price' => number_format($p['price'], 2),
            'thumb' => $p['images'][0],
            'url' => '/merch/amazon/' . $p['slug'],
            'badge' => 'amazon',
            'badge_label' => 'Amazon',
        );
    }
?>
    <style>
    /* The theme absolutely-positions the product name and floats the price,
       which overlap once a name wraps. Lay the card header out as a flex row. */
    .product-listing.extern-items .product-item-listing { height: auto; }
    .product-listing.extern-items .product-item-listing > h4 { display: flex; justify-content: space-between; align-items: baseline; gap: 12px; font-size: 17px; line-height: 1.3; margin: 0 0 12px; text-align: left; }
    .product-listing.extern-items h4 > span:first-child { position: static; max-width: none; flex: 1 1 auto; }
    .product-listing.extern-items h4 > span:last-child { float: none; white-space: nowrap; font-weight: 600; }
    .product-listing.extern-items .product-images { height: 240px; margin-top: 4px; position: relative; }
    .product-listing.extern-items .product-images img { position: static; height: 240px; transform: none; display: block; margin: 0 auto; max-width: 100%; }
    /* Source badge: where the item is fulfilled. */
    .merch-badge { position: absolute; top: 6px; left: 6px; z-index: 5; display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; letter-spacing: .02em; line-height: 1.4; }
    .merch-badge.onsite { background: #d8f5e3; color: #12703f; }
    .merch-badge.amazon { background: #232f3e; color: #ff9900; }
    </style>
    <main class="page-content">
    <!-- Classic Breadcrumbs-->
    <section class="breadcrumb-classic">
        <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/headers/shirts.jpg" class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
                <div class="shell">
                    <ul class="list-breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li><a href="/merch">Merch</a></li>
                        <li><?php echo htmlspecialchars($display); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section class="section-50">
        <div class="shell">
            <h1 class="text-center text-lg-left"><?php echo htmlspecialchars($display); ?></h1>
            <div class="range range-lg range-xs-center">
                <div class="cell-lg-12 cell-md-8">
                    <div class="range">
                        <div class="cell-lg-12">
                            <div class="inset-lg-right-45">
                                <ul class="list list-xl">
                                    <li>
                                        <p><span class="small">UNT Robotics <?php echo htmlspecialchars($display); ?></span><span class="text-darker">Support UNT Robotics &amp; look dapper while doing it!</span></p>
                                    </li>
                                </ul>
                                <div class="range range-lg-center">
                                    <div class="cell-lg-10 cell-sm-12">
                                        <div class="product-items-container">
                                            <?php if (empty($cards)) { ?>
                                                <p class="text-center" style="padding:40px 0;color:#8a908c;">No products here yet &mdash; check back soon.</p>
                                            <?php } foreach ($cards as $card) { ?>
                                                <div class="col-lg-6 col-sm-12 product-item product-listing extern-items">
                                                    <div class="product-container-pad">
                                                        <div class="product-item-listing">
                                                            <h4>
                                                                <span><?php echo htmlspecialchars($card['name']); ?></span>
                                                                <span><?php echo '$' . $card['price']; ?></span>
                                                            </h4>
                                                            <div class="product-images">
                                                                <span class="merch-badge <?php echo $card['badge']; ?>"><?php echo htmlspecialchars($card['badge_label']); ?></span>
                                                                <img src="<?php echo htmlspecialchars($card['thumb']); ?>" alt="<?php echo htmlspecialchars($card['name']); ?>"/>
                                                            </div>
                                                        </div>
                                                        <div class="product-item-action">
                                                            <a class="btn btn-primary" href="<?php echo htmlspecialchars($card['url']); ?>">View Product</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
}
?>
