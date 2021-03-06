<?php
require(BASE . '/api/printful/printful.php');

function merch_template($type, $search) {
    $printfulapi = new PrintfulCustomAPI();
?>
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
                        <li><?php echo $type; ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <section class="section-50">
        <div class="shell">
            <h1 class="text-center text-lg-left"><?php echo $type; ?></h1>
            <div class="range range-lg range-xs-center">
                <div class="cell-lg-12 cell-md-8">
                    <div class="range">
                        <div class="cell-lg-12">
                            <div class="inset-lg-right-45">
                                <ul class="list list-xl">
                                    <li>
                                        <p><span class="small">UNT Robotics <?php echo $type; ?></span><span class="text-darker">Support UNT Robotics &amp; look dapper while doing it!</span></p>
                                    </li>
                                </ul>
                                <div class="range range-lg-center">
                                    <div class="cell-lg-10 cell-sm-12">
                                        <div class="product-items-container">
                                            <?php
                                            $items = $printfulapi->get_products("{$search}");
                                            foreach ($items->get_results() as $item) {
                                                $product_price = $printfulapi->get_product_price($item->id);
                                                ?>
                                                <div class="col-lg-6 col-sm-12 product-item product-listing extern-items">
                                                    <div class="product-container-pad">
                                                        <div class="product-item-listing">
                                                            <h4>
                                                                <span><?php
                                                                    echo htmlspecialchars(
                                                                            preg_replace('@' . preg_quote($search) . '$@', '', $item->name)
                                                                    );
                                                                    ?></span>
                                                                <span><?php echo '$' . $product_price[0]; ?></span>
                                                            </h4>
                                                            <div class="product-images"><img src="<?php echo $item->thumbnail_url; ?>"  alt="<?php echo $item->name; ?>"/></div>
                                                        </div>
                                                        <div class="product-item-action">
                                                            <a id="buy-item-now" class="btn btn-primary" href="/merch/product/<?php echo $item->external_id; ?>/<?php echo post_slug($item->name); ?>">Buy Now</a>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
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