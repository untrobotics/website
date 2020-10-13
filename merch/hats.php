<?php
require('../template/top.php');
require(BASE . '/api/printful/printful.php');
require(BASE . '/template/functions/functions.php');

$printfulapi = new PrintfulCustomAPI();

head('Order Hats', true);
?>
<style>
	.product-items-container {
		margin-top: 20px;
	}
	.product-item {
    	display: flex;
    	margin-left: -1px;
		flex-direction: column;
		-ms-align-items: stretch;
		align-items: stretch;
	}
	
	.product-listing.hats .product-images {
		position: relative;
		height: 300px;
	}
	.product-listing.hats .product-images img {
		opacity: 1;
		position: absolute;
		height: 300px;
		top: 0;
		margin-top: 0px;
    	left: 50%;
    	transform: translateX(-50%);
	}
	
	.product-item-listing {
		display: flex;
		flex-direction: column;
		height: 333px;
	}
	.product-item-action button {
		width: 100%;
	}
	.product-listing.hats h4 > span {
		color: #fb5252;
	    float: right;
	    font-weight: 300;
	}
	.product-listing .product-container-pad {
    	margin: 8px 2px;
    	padding: 8px;
		border: 1px solid #e6e6e6;
	}
	#buy-hat-now {
		display: block;
	}
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
			  <li>Hats
			  </li>
			</ul>
		  </div>
		</div>
	  </div>
	</section>
	<section class="section-50">
	  <div class="shell">
		<h1 class="text-center text-lg-left">Hats</h1>
		<div class="range range-lg range-xs-center">
		  <div class="cell-lg-12 cell-md-8">
			<div class="range">
			  <div class="cell-lg-12">
				<div class="inset-lg-right-45">
				  <ul class="list list-xl">
					<li>
					  <p><span class="small">UNT Robotics Hats</span><span class="text-darker">Support UNT Robotics &amp; look dapper while doing it!</span></p>
					</li>
				  </ul>
					<div class="range range-lg-center">
						<div class="cell-lg-10 cell-sm-12">
							<div class="product-items-container">
							<?php
								//$products = $pf->get('products');
								$hats = $printfulapi->get_products();
								foreach ($hats->get_results() as $hat) {
									$product_price = $printfulapi->get_product_price($hat->id);
								?>
								<div class="col-lg-6 col-sm-12 product-item product-listing hats">
									<div class="product-container-pad">
										<div class="product-item-listing">
											<h4><?php echo htmlspecialchars($hat->name); ?> <span><?php echo '$' . $product_price[0]; ?></span></h4>
											<div class="product-images"><img src="<?php echo $hat->thumbnail_url; ?>" /></div>
										</div>
										<div class="product-item-action">
											<a id="buy-hat-now" class="btn btn-primary" href="/merch/product/<?php echo $hat->external_id; ?>/<?php echo post_slug($hat->name); ?>">Buy Now</a>
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
footer(false);
?>
<script>
	$('#buy-shirt-form').on('submit', function(e) {
		if ($('#choose-size').val() == 'none') {
			alert('Please choose a size.');
			return false;
		}
		if ($('#choose-colour').val() == 'none') {
			alert('Please choose a colour.');
			return false;
		}
	});
</script>