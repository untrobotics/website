<?php
require('../template/top.php');
require(BASE . '/template/functions/functions.php');
require_once(BASE . '/merch/includes/merch-data.php');

$slug = isset($_GET['id']) ? $_GET['id'] : '';
$product = merch_amazon_by_slug($slug);

$category_slug = $product ? merch_category_slug($product['category']) : 'shirts-hoodies';
$category_label = $product ? merch_category_display($product['category']) : 'Merch';

if ($product) {
    // Per-product Open Graph preview so a shared link shows the garment.
    $og_title = $product['name'];
    $og_description = 'UNT Robotics ' . $product['name'] . ' — available on Amazon with Prime shipping.';
    $__ogi = $product['images'][0];
    $og_image = (strpos($__ogi, 'http') === 0) ? $__ogi : 'https://www.untrobotics.com' . $__ogi;
    head("Buy {$product['name']}", true);
} else {
    head('Invalid Product', true);
}
?>
<style>
    .merch-gallery { display: flex; gap: 12px; align-items: flex-start; max-width: 100%; }
    .merch-image { margin: 0; flex: 1 1 auto; max-width: 440px; display: block; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; background: #fff; order: 1; }
    .merch-image img { width: 100%; display: block; }
    .merch-thumbs { display: flex; flex-direction: column; gap: 8px; margin: 0; flex: 0 0 auto; order: 0; }
    .merch-thumb { padding: 0; border: 1px solid #cacaca; border-radius: 6px; background: #fff; cursor: pointer; width: 64px; height: 64px; overflow: hidden; line-height: 0; transition: border-color .1s; }
    .merch-thumb:hover { border-color: #999; }
    .merch-thumb.is-active { border-color: #1f1f1f; box-shadow: inset 0 0 0 2px #1f1f1f; }
    .merch-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    @media (max-width: 767px) {
        .merch-gallery { flex-direction: column; align-items: center; }
        .merch-image { order: 0; max-width: 100%; }
        .merch-thumbs { order: 1; flex-direction: row; flex-wrap: wrap; justify-content: center; }
    }
    .product-price { color: red; font-size: 18pt; }
    .amazon-badge { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: #232f3e; color: #ff9900; margin-left: 8px; vertical-align: middle; }
    .front-back-info { margin: 18px 0 0; }
    .front-back-info > div { display: flex; gap: 8px; padding: 7px 0; border-bottom: 1px solid #eee; font-size: 15px; }
    .front-back-info > div > span:first-child { font-weight: 800; min-width: 56px; }
    .btn-amazon { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; max-width: 400px; height: 48px; border: 0; border-radius: 6px; background: #ff9900; color: #111; font-size: 16px; font-weight: 700; cursor: pointer; margin-top: 22px; }
    .btn-amazon:hover { background: #f08c00; color: #111; }
    .amazon-note { font-size: 12px; color: #8a908c; margin-top: 10px; max-width: 400px; }
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
                        <li><a href="/merch/<?php echo htmlspecialchars($category_slug); ?>"><?php echo htmlspecialchars($category_label); ?></a></li>
                        <li>Product</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php if ($product) { ?>
    <section class="section-50">
        <div class="shell">
            <div class="range range-lg range-xs-center">
                <div class="cell-lg-12 cell-md-8">
                    <div class="range merch-header">
                        <div class="cell-lg-7 cell-md-12">
                            <h1 class="text-center text-lg-left"><?php echo htmlspecialchars($product['name']); ?><span class="amazon-badge">Amazon</span></h1>
                            <div class="product-price"><?php echo '$' . number_format($product['price'], 2); ?></div>
                        </div>
                        <div class="cell-lg-5 cell-md-12">
                            <p class="merch-tagline text-lg-right"><span class="small">UNT Robotics Merchandise</span><span class="text-darker">Support UNT Robotics &amp; look dapper while doing it!</span></p>
                        </div>
                    </div>

                    <div class="range">
                        <div class="col-lg-6 col-md-12 col-lg-push-6">
                            <div class="merch-gallery">
                                <div class="merch-image">
                                    <a id="merch-hero-link" href="<?php echo htmlspecialchars($product['images'][0]); ?>" target="_blank" rel="noopener">
                                        <img id="merch-hero-img" src="<?php echo htmlspecialchars($product['images'][0]); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"/>
                                    </a>
                                </div>
                                <?php if (count($product['images']) > 1) { ?>
                                <div class="merch-thumbs" id="merch-thumbs">
                                    <?php foreach ($product['images'] as $gi => $img) { ?>
                                    <button type="button" class="merch-thumb<?php echo $gi === 0 ? ' is-active' : ''; ?>" data-full="<?php echo htmlspecialchars($img); ?>">
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="product view"/>
                                    </button>
                                    <?php } ?>
                                </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-12 col-lg-pull-6">
                            <div class="front-back-info">
                                <div><span>Front</span><span><?php echo htmlspecialchars($product['front']); ?></span></div>
                                <div><span>Back</span><span><?php echo $product['back'] !== '' ? htmlspecialchars($product['back']) : '<em>None</em>'; ?></span></div>
                            </div>
                            <a class="btn-amazon" href="<?php echo htmlspecialchars($product['amazon_url']); ?>" target="_blank" rel="noopener">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M.045 18.02c.072-.116.187-.124.348-.022 3.636 2.11 7.594 3.166 11.87 3.166 2.852 0 5.668-.533 8.447-1.595l.315-.14c.138-.06.234-.1.293-.13.226-.088.39-.046.525.13.12.174.09.336-.12.48-.256.19-.6.41-1.006.654-1.244.743-2.64 1.316-4.185 1.726a17.617 17.617 0 01-4.552.615c-2.855 0-5.532-.5-8.033-1.496-2.5-.998-4.735-2.4-6.705-4.207-.11-.1-.164-.198-.164-.288 0-.06.024-.11.07-.16zm6.615-6.402c0-.93.23-1.727.688-2.39.458-.665 1.086-1.167 1.883-1.51.73-.315 1.63-.54 2.696-.674.362-.043 .952-.1 1.77-.17v-.34c0-.86-.093-1.437-.28-1.732-.28-.4-.72-.6-1.32-.6h-.16c-.44.04-.82.18-1.14.42-.32.24-.52.57-.6.99-.05.27-.18.42-.4.46l-2.3-.29c-.23-.05-.35-.17-.35-.37 0-.04.01-.09.02-.15.23-1.18.78-2.06 1.66-2.62.88-.57 1.9-.88 3.08-.94h.5c1.5 0 2.68.39 3.53 1.16.13.13.25.27.36.42.11.15.19.28.25.4.06.12.11.29.16.51.05.22.08.38.1.48.02.1.03.31.04.63.01.32.02.51.02.57v5.46c0 .39.06.75.17 1.07.11.32.22.55.33.69l.53.7c.09.13.14.26.14.38 0 .13-.07.25-.2.36-1.34 1.17-2.07 1.8-2.19 1.9-.2.16-.44.18-.72.06-.23-.19-.43-.37-.6-.54l-.36-.4a10.6 10.6 0 01-.34-.45l-.32-.47c-.87.95-1.73 1.54-2.58 1.77-.53.15-1.19.22-1.97.22-1.2 0-2.19-.37-2.96-1.11-.77-.74-1.16-1.79-1.16-3.15zm3.34-.39c0 .61.15 1.1.46 1.47.31.37.72.55 1.25.55.05 0 .11-.01.19-.02.08-.01.13-.02.16-.02.68-.18 1.2-.62 1.57-1.32.18-.31.31-.65.4-1.02.09-.37.13-.67.14-.9.01-.23.02-.61.02-1.14v-.61c-.78 0-1.37.05-1.77.16-1.09.31-1.64 1.02-1.64 2.11zm8.83 8.28c.02-.03.05-.06.1-.1.29-.19.57-.32.83-.39a6.7 6.7 0 011.28-.19c.11-.01.22 0 .32.02.5.05.8.13.9.26.05.06.07.15.07.27v.1c0 .34-.09.74-.28 1.2-.19.46-.45.83-.79 1.11-.05.04-.09.06-.13.06-.02 0-.04 0-.06-.01-.06-.03-.08-.08-.05-.16.36-.85.54-1.44.54-1.77 0-.1-.02-.18-.06-.23-.1-.12-.38-.18-.83-.18-.17 0-.36.01-.59.03-.24.03-.47.06-.67.09-.06 0-.1-.01-.12-.03-.02-.02-.02-.04-.01-.06 0-.01.01-.03.02-.05z"/></svg>
                                Buy on Amazon
                            </a>
                            <p class="amazon-note">Sold and shipped by Amazon &mdash; opens in a new tab. Prices and availability are set on Amazon.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php } else { ?>
    <section class="section-50">
        <div class="shell">
            <div class="alert alert-danger">Uh oh! This product either does not exist or is invalid.</div>
        </div>
    </section>
    <?php } ?>
</main>
<?php footer(false); ?>
<?php if ($product) { ?>
<script>
(function () {
    var heroImg = document.getElementById('merch-hero-img');
    var heroLink = document.getElementById('merch-hero-link');
    var thumbs = document.querySelectorAll('.merch-thumb');
    thumbs.forEach(function (t) {
        t.addEventListener('click', function () {
            var full = t.getAttribute('data-full');
            if (heroImg) { heroImg.src = full; }
            if (heroLink) { heroLink.setAttribute('href', full); }
            thumbs.forEach(function (x) { x.classList.remove('is-active'); });
            t.classList.add('is-active');
        });
    });
})();
</script>
<?php } ?>
