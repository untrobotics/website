<?php
require('../template/top.php');
require(BASE . '/api/printful/printful.php');
require(BASE . '/template/functions/functions.php');
require(BASE . '/template/functions/payment_button.php');

$printfulapi = new PrintfulCustomAPI();

$product = null;
$external_product_id = $_GET['id'];
$product_name = $_GET['desc'];
if (isset($_GET['variant'])) {
	$variant_id = $_GET['variant'];
} else {
	$variant_id = -1;
}
$selected_product_variant_index = 0;

$product_can_be_handled = true;
$validation_error = null;

if (!empty($external_product_id)) {
	try {
		$product = $printfulapi->get_product('@' . $external_product_id);
		//error_log("ID: " . $product->get_variants()[0]->get_product()->get_product_id());
		$catalog_product = $printfulapi->get_catalog_product($product->get_variants()[0]->get_product()->get_product_id());
		
		//var_dump($product);
		if (!empty($product_name)) {
			$product_description = post_slug($product->get_name());
			if (!preg_match("@^{$product_description}$@i", $product_name)) {
				// validation error: name does not match slug
				$product_can_be_handled = false;
				$validation_error = "Mismatched post slug and product description.";
			}
		}
	} catch (PrintfulCustomAPIException $ex) {
		$product_can_be_handled = false;
		$validation_error = "Failed to retrieve the requested product.";
	}
	
} else {
	// validation error: null id
	$product_can_be_handled = false;
	$validation_error = "Invalid or missing product ID.";
}

if ($product_can_be_handled) {
	foreach ($product->get_variants() as $index => $variant) {
		if ($variant->get_variant_id() == $variant_id) {
			$selected_product_variant_index = $index;
			break;
		}
	}
	
	$selected_variant = $product->get_variants()[$selected_product_variant_index];
	
	$mockup_file = $selected_variant->get_file_by_type(PrintfulVariantFilesTypes::PREVIEW);
	if ($mockup_file == null) {
		// TODO
		$mockup_file = "";
	}
	$back_file = $selected_variant->get_file_by_type(PrintfulVariantFilesTypes::BACK);
	
	head("Buy {$product->get_name()}", true);
	$category_name = strtolower(preg_replace('@^.*\(([^()]+)\)$@i', '$1', $product->get_name()));
	if($category_name !== 'gear' && $category_name[-1]!=='s'){
	$category_name .= 's';
	}

} else {
	head("Invalid Product", true);
}

function get_variant_variant($variant_name) {
	return preg_replace("@.* - (.+)$@i", "$1", $variant_name);
}
?>
<style>
	@media (max-width: 1200px) {
		.merch-tagline, .merch-image {
			float: none !important;
		}
	}
	.merch-tagline, .merch-image {
		float: right;
	}
	.merch-section {
		display: flex;
		flex-direction: row;
		align-items: stretch;
	}
	.merch-section > div:nth-child(2n) {
		text-align: right;
	}
	
	.merch-image {
		display: inline-block;
		max-width: 500px;
		 border: 1px solid #cacaca;
	}
	.merch-image img {
		width: 100%;
	}
	.merch-section h6 {
		border-bottom: 1px solid #a7a7a7;
		margin-bottom: 5px;
	}
	.product-price {
		color: red;
		font-size: 18pt;
	}
	/*
	.whitebg {
		background: white;
		z-index: 1;
		height: 50px;
		width: 100px;
		position: absolute;
		display: inline-block;
	}
	
	.blackbg {
		background: black;
		z-index: 2;
		height: 50px;
		width: 100px;
		position: absolute;
		display: inline-block;
	}
	.variant-btn-container {
		display: block;
		height: 50px;
		width: 100px;
		position: relative;
	}
	.variant-btn {
		border: 1px solid #d8d8d8;
		z-index: 4;
		position: absolute;
		mix-blend-mode: screen;
	}
	.variant-btn + span {
		position: absolute;
		font-family: Arial, Helvetica;
		mix-blend-mode: difference;
		color: white;
		z-index: 3;
	}
	*/
	.variant-btn {
		border: 1px solid #d8d8d8;
		color: white;
		text-shadow: 1px 1px 0 black, -1px -1px 0 black, 1px -1px 0 black, -1px 1px 0 black;
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
	<?php if ($product_can_be_handled) { ?>
			  <li><a href="/merch/<?php echo $category_name; ?>"><?php echo $category_name; ?></a></li>
	<?php } ?>
			  <li>Product</li>
			</ul>
		  </div>
		</div>
	  </div>
	</section>
	
	<?php if ($product_can_be_handled) { ?>
	<section class="section-50">
	  <div class="shell">
		<div class="range range-lg range-xs-center">
		  <div class="cell-lg-12 cell-md-8">

		<div class="range merch-header">
			<div class="cell-lg-7 cell-md-12">
				<h1 class="text-center text-lg-left"><?php echo htmlspecialchars($product->get_name()); ?></h1>
				<div class="product-price"><?php
					$fmt = new NumberFormatter( 'en_US', NumberFormatter::CURRENCY );
					echo $fmt->formatCurrency($product->get_product_price(), $product->get_product_currency());
					?><small> &amp; <strong>FREE</strong> SHIPPING</small>
				</div>
			</div>
			<div class="cell-lg-5 cell-md-12">
				<p class="merch-tagline"><span class="small">UNT Robotics Merchandise</span><span class="text-darker">Support UNT Robotics &amp; look dapper while doing it!</span></p>
			</div>
		</div>
			  
			<div class="range">
			  <div class="cell-lg-12 merch-section">
				  
					<div class="range">
						<div class="col-lg-6 col-md-12 col-lg-push-6">
							<div class="merch-image">
								<a href="<?php echo $mockup_file->get_url() ? $mockup_file->get_url() : $mockup_file->get_preview_url(); ?>">
									<img src="<?php echo $mockup_file->get_preview_url(); ?>"/>
								</a>
							</div>
							<?php
								if ($back_file && false) { // TODO: find a back picture
								?>
									<div class="merch-image">
										<a href="<?php echo $back_file->get_url() ? $back_file->get_url() : $back_file->get_preview_url(); ?>">
											<img src="<?php echo $back_file->get_preview_url(); ?>"/>
										</a>
									</div>
								<?php
								}
							?>
						</div>
						<div class="col-lg-6 col-md-12 col-lg-pull-6">
						  <h6 style="margin-top: 25px;"><strong><?php echo $catalog_product->get_type_name(); ?> variants</strong></h6>
								<?php
									foreach ($product->get_variants() as $index => $variant) {
										$variant_name = preg_replace("@.* - (.+)$@i", "$1", $variant->get_name());
										$caps_variant_name = strtoupper($variant_name);
										//$variant_colours = constant("PrintfulVariantColours::{$caps_variant_name}");

										$colour_code = null;
										foreach ($catalog_product->get_variants() as $catalog_variant) {
											if ($catalog_variant->get_id() == $variant->get_product()->get_variant_id()) {
												$colour_code = $catalog_variant->get_colour_code();
											}
										}
										?>
							<span class="variant-btn-container">
								<!--<span class="whitebg"></span>
								<span class="blackbg"></span>-->
								<a
										class="btn variant-btn"
										style="
											   background-color: <?php echo $colour_code; ?>;
										"
									href="/merch/product/<?php echo $external_product_id; ?>/<?php echo post_slug($product->get_name()); ?>/<?php echo $variant->get_product()->get_variant_id(); ?>">
									<span><?php echo $variant_name; ?></span>
								</a>
							</span>
										<?php
									}
								?>

							<?php
								preg_match("@^(.+?)•@ims", $catalog_product->get_description(), $m);
								//var_dump($m);
								$description = "";
								if (count($m)) {
									$description = trim($m[1]);
								}
								preg_match_all("@• (.+)\n@i", $catalog_product->get_description(), $m);
								//var_dump($m);
								$other_info = array();
								foreach ($m[1] as $match) {
									$other_info[] = trim($match);
								}
							?>

							<ul style="list-style: circle; margin-left: 25px; margin-top: 20px; text-align: left;">
								<li><strong>Brand:</strong> <?php echo $catalog_product->get_brand(); ?></li>
								<li><strong>Model:</strong> <?php echo $catalog_product->get_model(); ?></li>
								<?php
										if (!empty($description)) {
								?>
								<li><strong>Description:</strong> <?php echo $description; ?></li>
								<?php
										}
								?>
								<li style="visibility: hidden;"></li>
								<?php
									foreach ($other_info as $info) {
										?>
								<li><?php echo $info; ?></li>
										<?php
									}
								?>
							</ul>
							<div class="offset-top-20">
								<?php
									// PayPal Smart Buttons (Orders v2) render here. The price is
									// recomputed server-side from Printful by the create endpoint;
									// the client only sends the product + variant.
								?>
								<div id="paypal-button-container"></div>
								<div class="offset-top-10">
									<button id="stripe-pay-button" type="button" class="btn btn-primary"
										data-product="<?php echo htmlspecialchars($external_product_id); ?>"
										data-variant="<?php echo htmlspecialchars($selected_variant->get_id()); ?>">
										Pay with Card / Apple Pay
									</button>
								</div>
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
	<?php } else { ?>
	<section class="section-50">
	  	<div class="shell">
			<div class="alert alert-danger">Uh oh! This product either does not exist or is invalid.</div>
		</div>
	</section>
	<?php } ?>
</main>
<?php
footer(false);
?>
<script src="https://js.stripe.com/v3/"></script>
<?php if ($product_can_be_handled) {
	// PayPal JS SDK client id for the active environment (sandbox flag is set by
	// the auth() call inside head() for sandbox users; guests/live get the live id).
	$paypal_client_id = $untrobotics->get_sandbox() ? PAYPAL_SANDBOX_CLIENT_ID : PAYPAL_CLIENT_ID;
?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars($paypal_client_id); ?>&currency=USD"></script>
<script>
	// PayPal Smart Buttons (Orders v2). The server recomputes the price from
	// Printful; we only send which product/variant was chosen.
	const PAYPAL_MERCH_PRODUCT = <?php echo json_encode($external_product_id); ?>;
	const PAYPAL_MERCH_VARIANT = <?php echo json_encode((string) $selected_variant->get_id()); ?>;
	if (window.paypal) {
		paypal.Buttons({
			createOrder: function() {
				const params = new URLSearchParams();
				params.set('source', 'merch');
				params.set('product', PAYPAL_MERCH_PRODUCT);
				params.set('variant', PAYPAL_MERCH_VARIANT);
				return fetch('/api/paypal/orders/create.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: params.toString(),
					credentials: 'same-origin'
				})
					.then(r => r.json())
					.then(d => {
						if (!d || !d.id) {
							throw new Error((d && d.error) ? d.error : 'Unable to start checkout.');
						}
						return d.id;
					});
			},
			onApprove: function(data) {
				return fetch('/api/paypal/orders/capture.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify({ order_id: data.orderID }),
					credentials: 'same-origin'
				})
					.then(r => r.json())
					.then(d => {
						if (d && d.success) {
							window.location = '/merch/buy/complete';
						} else {
							alert((d && d.error) ? d.error : 'Your payment could not be completed.');
						}
					});
			},
			onError: function(err) {
				alert('An error occurred with PayPal checkout. Please try again.');
			}
		}).render('#paypal-button-container');
	}
</script>
<?php } ?>
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

	// Stripe Checkout (Card / Apple Pay). The server recomputes the price from
	// Printful; we only send which product/variant was chosen.
	$('#stripe-pay-button').on('click', function() {
		const $btn = $(this);
		$btn.prop('disabled', true).text('Redirecting...');
		const params = new URLSearchParams();
		params.set('source', 'merch');
		params.set('product', $btn.data('product'));
		params.set('variant', $btn.data('variant'));
		fetch('/api/stripe/create-checkout-session', {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: params.toString(),
			credentials: 'same-origin'
		})
			.then(r => r.json())
			.then(data => {
				if (data && data.url) {
					window.location = data.url;
				} else {
					alert((data && data.error) ? data.error : 'Unable to start checkout.');
					$btn.prop('disabled', false).text('Pay with Card / Apple Pay');
				}
			})
			.catch(() => {
				alert('Unable to start checkout. Please try again.');
				$btn.prop('disabled', false).text('Pay with Card / Apple Pay');
			});
	});
</script>