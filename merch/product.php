<?php
require('../template/top.php');
require(BASE . '/api/printful/printful.php');
require(BASE . '/template/functions/functions.php');

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
	// When no specific variant is requested, prefer a brand-colour (green)
	// variant as the default instead of whatever Printful lists first (often
	// an off-brand colour such as blue).
	if ($variant_id == -1) {
		$preferred_colours = array('kelly', 'green', 'forest', 'irish', 'military');
		foreach ($product->get_variants() as $index => $variant) {
			$vname = strtolower($variant->get_name());
			foreach ($preferred_colours as $pc) {
				if (strpos($vname, $pc) !== false) { $selected_product_variant_index = $index; break 2; }
			}
		}
	}

	$selected_variant = $product->get_variants()[$selected_product_variant_index];
	
	// Printful's per-variant "preview" is the only wearable garment shot it
	// returns; its other files are flat print artwork. Take the preview here
	// and append back/side angles from the generated mockups further down.
	$build_gallery = function ($variant) {
		$images = array();
		$seen = array();
		foreach ($variant->get_files() as $file) {
			if ($file->get_type() !== 'preview') { continue; }
			$preview = $file->get_preview_url();
			if (empty($preview) || isset($seen[$preview])) { continue; }
			$seen[$preview] = true;
			$images[] = array(
				'preview' => $preview,
				'full' => $file->get_url() ? $file->get_url() : $preview,
				'thumb' => $file->get_thumbnail_url() ? $file->get_thumbnail_url() : $preview,
				'type' => 'preview',
			);
		}
		return $images;
	};
	$gallery = $build_gallery($selected_variant);
	if (empty($gallery)) {
		$pf = $selected_variant->get_file_by_type(PrintfulVariantFilesTypes::PREVIEW);
		if ($pf !== null && $pf->get_preview_url()) {
			$gallery[] = array('preview' => $pf->get_preview_url(), 'full' => $pf->get_url() ? $pf->get_url() : $pf->get_preview_url(), 'thumb' => $pf->get_preview_url(), 'type' => 'preview');
		}
	}
	// Every variant's gallery + sync id, for instant client-side colour switching.
	$variants_js = array();
	foreach ($product->get_variants() as $variant) {
		$variants_js[$variant->get_product()->get_variant_id()] = array(
			'sync' => (string) $variant->get_id(),
			'gallery' => $build_gallery($variant),
		);
	}
	$default_catalog_variant_id = $selected_variant->get_product()->get_variant_id();
	// --- Colour + size pickers --------------------------------------------
	// Printful variant names are "<product> / <colour> / <size>" (or just
	// "<size>" for single-colour products). Split them so the page can show a
	// clean colour-swatch + size-button picker instead of one button per combo.
	$size_rank = array('xxs'=>0,'xs'=>1,'s'=>2,'m'=>3,'l'=>4,'xl'=>5,'2xl'=>6,'xxl'=>6,'3xl'=>7,'xxxl'=>7,'4xl'=>8,'5xl'=>9,'6xl'=>10);
	$variant_colours = array();
	$variant_sizes = array();
	$variant_combo = array();
	// Extra angle mockups rendered by merch/tools/generate-mockups.php, keyed by
	// external_id -> colour ('' for a single design) -> [{view, path}]. Appended
	// to Printful's front preview so the gallery shows back/side angles too.
	$generated_mockups = array();
	$mockups_file = BASE . '/images/merch/generated/mockups.json';
	if (is_readable($mockups_file)) {
		$decoded = json_decode(file_get_contents($mockups_file), true);
		if (is_array($decoded)) {
			$generated_mockups = $decoded;
		}
	}
	$apply_generated_mockups = function ($images, $colour) use ($generated_mockups, $external_product_id) {
		$extra = null;
		if (isset($generated_mockups[$external_product_id][$colour])) {
			$extra = $generated_mockups[$external_product_id][$colour];
		} elseif (isset($generated_mockups[$external_product_id][''])) {
			$extra = $generated_mockups[$external_product_id][''];
		}
		if (!$extra) {
			return $images;
		}
		foreach ($extra as $m) {
			$images[] = array('preview' => $m['path'], 'full' => $m['path'], 'thumb' => $m['path'], 'type' => $m['view']);
		}
		return $images;
	};
	$split_label = function ($name) use ($product) {
		$label = trim(str_ireplace($product->get_name(), '', $name), " /-");
		$parts = array_map('trim', explode(' / ', $label));
		if (count($parts) >= 2) { $size = array_pop($parts); return array(implode(' / ', $parts), $size); }
		return array('', $label);
	};
	foreach ($product->get_variants() as $variant) {
		$cat = $variant->get_product()->get_variant_id();
		list($colour, $size) = $split_label($variant->get_name());
		$code = null;
		foreach ($catalog_product->get_variants() as $cv) { if ($cv->get_id() == $cat) { $code = $cv->get_colour_code(); break; } }
		if ($colour !== '' && !isset($variant_colours[$colour])) { $variant_colours[$colour] = $code; }
		if (!isset($variant_sizes[$size])) { $lc = strtolower($size); $variant_sizes[$size] = is_numeric($size) ? (100 + floatval($size)) : (isset($size_rank[$lc]) ? $size_rank[$lc] : 50); }
		$variant_combo[$colour . '|' . $size] = (string) $cat;
		$variants_js[$cat]['colour'] = $colour;
		$variants_js[$cat]['size'] = $size;
		$variants_js[$cat]['gallery'] = $apply_generated_mockups($variants_js[$cat]['gallery'], $colour);
	}
	asort($variant_sizes);
	list($default_colour, $default_size) = $split_label($selected_variant->get_name());
	$gallery = $apply_generated_mockups($gallery, $default_colour);
	
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
	.merch-tagline {
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
		.merch-gallery { display: flex; gap: 12px; align-items: flex-start; max-width: 100%; }
		.merch-image { float: none; margin: 0; flex: 1 1 auto; max-width: 440px; display: block; border: 1px solid #e5e5e5; border-radius: 8px; overflow: hidden; background: #fff; order: 1; }
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
		.variant-btn.variant-active { outline: 2px solid #1f1f1f; outline-offset: 1px; }
		.variant-picker { margin: 16px 0; text-align: left; }
		.variant-picker-label { font-weight: 600; margin-bottom: 8px; color: #1f1f1f; }
		.colour-swatches { display: flex; flex-wrap: wrap; gap: 10px; }
		.colour-swatch { width: 38px; height: 38px; border-radius: 50%; border: 2px solid #d0d0d0; cursor: pointer; padding: 0; transition: transform .1s; }
		.colour-swatch:hover { transform: scale(1.08); }
		.colour-swatch.is-active { border-color: #1f1f1f; box-shadow: 0 0 0 2px #fff, 0 0 0 4px #1f1f1f; }
		.size-buttons { display: flex; flex-wrap: wrap; gap: 8px; }
		.size-btn { min-width: 46px; padding: 9px 12px; border: 1px solid #cacaca; background: #fff; color: #1f1f1f; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all .1s; }
		.size-btn:hover { border-color: #1f1f1f; }
		.size-btn.is-active { background: #1f1f1f; color: #fff; border-color: #1f1f1f; }
		.size-btn:disabled { opacity: .35; cursor: not-allowed; text-decoration: line-through; }
		.pay-buttons { max-width: 440px; }
		.pay-buttons #express-checkout-element:empty { display: none; }
		.pay-buttons #paypal-button-container { margin-top: 10px; }
		.pay-buttons .offset-top-10 { margin-top: 10px; }
		.pay-buttons #stripe-pay-button { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 46px; margin: 0; border: 0; border-radius: 4px; background: #635bff; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
		.pay-buttons .stripe-note { text-align: center; font-size: 11px; color: #9aa0a6; margin-top: 6px; }
		.pay-buttons #stripe-pay-button:hover { background: #544dff; }
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
							<?php if (!empty($gallery)) { $hero = $gallery[0]; ?>
							<div class="merch-gallery">
								<div class="merch-image">
									<a id="merch-hero-link" href="<?php echo htmlspecialchars($hero['full']); ?>" target="_blank" rel="noopener">
										<img id="merch-hero-img" src="<?php echo htmlspecialchars($hero['preview']); ?>" alt="<?php echo htmlspecialchars($product->get_name()); ?>"/>
									</a>
								</div>
								<?php if (count($gallery) > 1) { ?>
								<div class="merch-thumbs" id="merch-thumbs">
									<?php foreach ($gallery as $gi => $img) { ?>
									<button type="button" class="merch-thumb<?php echo $gi === 0 ? ' is-active' : ''; ?>" data-preview="<?php echo htmlspecialchars($img['preview']); ?>" data-full="<?php echo htmlspecialchars($img['full']); ?>">
										<img src="<?php echo htmlspecialchars($img['thumb']); ?>" alt="product view"/>
									</button>
									<?php } ?>
								</div>
								<?php } ?>
							</div>
							<?php } ?>
						</div>
						<div class="col-lg-6 col-md-12 col-lg-pull-6">
							<?php if (count($variant_colours) > 1) { ?>
							<div class="variant-picker">
								<div class="variant-picker-label">Colour: <span id="selected-colour-name"><?php echo htmlspecialchars($default_colour); ?></span></div>
								<div class="colour-swatches" id="colour-swatches">
									<?php foreach ($variant_colours as $cname => $ccode) { ?>
									<button type="button" class="colour-swatch<?php echo $cname === $default_colour ? ' is-active' : ''; ?>" data-colour="<?php echo htmlspecialchars($cname); ?>" title="<?php echo htmlspecialchars($cname); ?>" style="background-color: <?php echo htmlspecialchars($ccode ? $ccode : '#cccccc'); ?>;"></button>
									<?php } ?>
								</div>
							</div>
							<?php } ?>
							<div class="variant-picker">
								<div class="variant-picker-label">Size</div>
								<div class="size-buttons" id="size-buttons">
									<?php foreach ($variant_sizes as $sname => $srank) { ?>
									<button type="button" class="size-btn<?php echo $sname === $default_size ? ' is-active' : ''; ?>" data-size="<?php echo htmlspecialchars($sname); ?>"><?php echo htmlspecialchars($sname); ?></button>
									<?php } ?>
								</div>
							</div>
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
								<?php $brand = trim((string) $catalog_product->get_brand()); if ($brand !== '') { ?><li><strong>Brand:</strong> <?php echo $brand; ?></li><?php } ?>
								<?php $model = trim((string) $catalog_product->get_model()); if ($model !== '') { ?><li><strong>Model:</strong> <?php echo $model; ?></li><?php } ?>
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
							<div class="offset-top-20 pay-buttons">
								<?php
									// PayPal Smart Buttons (Orders v2) render here. The price is
									// recomputed server-side from Printful by the create endpoint;
									// the client only sends the product + variant.
								?>
								<div id="express-checkout-element" style="margin-bottom:10px;"></div>
								<div id="paypal-button-container"></div>
								<div class="offset-top-10">
									<button id="stripe-pay-button" type="button"
										data-product="<?php echo htmlspecialchars($external_product_id); ?>"
										data-variant="<?php echo htmlspecialchars($selected_variant->get_id()); ?>">
										<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
										<span class="pay-label">Pay with card (Stripe)</span>
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
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars($paypal_client_id); ?>&currency=USD&disable-funding=card"></script>
<script>
	// Express Checkout Element: native Apple Pay / Google Pay / Link with US
	// shipping collection (physical goods). The PaymentIntent recomputes the
	// selected variant's price server-side; the wallet's shipping address is
	// attached at confirm and read back by the payment_intent webhook.
	(function () {
		var stripe = window.Stripe ? Stripe('<?php echo htmlspecialchars(STRIPE_PUBLISHABLE_KEY, ENT_QUOTES); ?>') : null;
		if (!stripe) { return; }
		var amountCents = <?php echo intval(round($product->get_product_price() * 100)); ?> || 1000;
		var mElements = stripe.elements({ mode: 'payment', amount: amountCents, currency: 'usd' });
		var ece = mElements.create('expressCheckout', {
			paymentMethods: { applePay: 'auto', googlePay: 'auto', link: 'never', amazonPay: 'never', paypal: 'never', klarna: 'never' },
			shippingAddressRequired: true,
			allowedShippingCountries: ['US'],
			shippingRates: [{ id: 'free', displayName: 'Free shipping', amount: 0 }]
		});
		ece.mount('#express-checkout-element');
		ece.on('shippingaddresschange', function (event) { event.resolve(); });
		ece.on('confirm', function (event) {
			mElements.submit().then(function (sub) {
				if (sub.error) { alert(sub.error.message); return; }
				var params = new URLSearchParams();
				params.set('source', 'merch');
				params.set('product', <?php echo json_encode($external_product_id); ?>);
				params.set('variant', window.MERCH_CURRENT_VARIANT || <?php echo json_encode((string) $selected_variant->get_id()); ?>);
				return fetch('/api/stripe/create-payment-intent.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params.toString(), credentials: 'same-origin' })
					.then(function (r) { return r.json(); })
					.then(function (d) {
						if (!d || !d.clientSecret) { alert((d && d.error) || 'Unable to start payment.'); return; }
						var confirmParams = { return_url: location.origin + '/merch/buy/complete' };
						var sa = event.shippingAddress;
						if (sa && sa.address) {
							confirmParams.shipping = {
								name: sa.name || '',
								address: {
									line1: sa.address.line1 || '',
									line2: sa.address.line2 || '',
									city: sa.address.city || '',
									state: sa.address.state || '',
									postal_code: sa.address.postal_code || '',
									country: sa.address.country || 'US'
								}
							};
						}
						return stripe.confirmPayment({ elements: mElements, clientSecret: d.clientSecret, confirmParams: confirmParams })
							.then(function (res) { if (res.error) { alert(res.error.message); } });
					});
			}).catch(function () { alert('Unable to start payment.'); });
		});
	})();
</script>
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
				params.set('variant', window.MERCH_CURRENT_VARIANT || PAYPAL_MERCH_VARIANT);
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

	// Stripe Checkout (Card). The server recomputes the price from
	// Printful; we only send which product/variant was chosen.
	$('#stripe-pay-button').on('click', function() {
		const $btn = $(this);
		$btn.prop('disabled', true).find('.pay-label').text('Redirecting...');
		const params = new URLSearchParams();
		params.set('source', 'merch');
		params.set('product', $btn.data('product'));
		params.set('variant', window.MERCH_CURRENT_VARIANT || $btn.data('variant'));
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
					$btn.prop('disabled', false).find('.pay-label').text('Pay with card (Stripe)');
				}
			})
			.catch(() => {
				alert('Unable to start checkout. Please try again.');
				$btn.prop('disabled', false).find('.pay-label').text('Pay with card (Stripe)');
			});
	});
</script>
<?php if ($product_can_be_handled) { ?>
<script>
(function () {
	window.MERCH_CURRENT_VARIANT = <?php echo json_encode((string) $selected_variant->get_id()); ?>;
	var VARIANTS = <?php echo json_encode($variants_js); ?>;
	var heroImg = document.getElementById("merch-hero-img");
	var heroLink = document.getElementById("merch-hero-link");
	var thumbWrap = document.getElementById("merch-thumbs");
	function setHero(preview, full) {
		if (heroImg) { heroImg.src = preview; }
		if (heroLink && full) { heroLink.setAttribute("href", full); }
	}
	function bindThumbs() {
		if (!thumbWrap) { return; }
		var thumbs = thumbWrap.querySelectorAll(".merch-thumb");
		thumbs.forEach(function (t) {
			t.addEventListener("click", function () {
				setHero(t.getAttribute("data-preview"), t.getAttribute("data-full"));
				thumbs.forEach(function (x) { x.classList.remove("is-active"); });
				t.classList.add("is-active");
			});
		});
	}
	function renderGallery(images) {
		if (!thumbWrap || !images || !images.length) { return; }
		var html = "";
		images.forEach(function (img, i) {
			html += '<button type="button" class="merch-thumb' + (i === 0 ? " is-active" : "") + '" data-preview="' + img.preview + '" data-full="' + img.full + '"><img src="' + img.thumb + '" alt="product view"/></button>';
		});
		thumbWrap.innerHTML = html;
		setHero(images[0].preview, images[0].full);
		bindThumbs();
	}
	bindThumbs();
	var COMBO = <?php echo json_encode($variant_combo); ?>;
	var selColour = <?php echo json_encode($default_colour); ?>;
	var selSize = <?php echo json_encode($default_size); ?>;
	var swatches = document.querySelectorAll(".colour-swatch");
	var sizeBtns = document.querySelectorAll(".size-btn");
	var colourNameEl = document.getElementById("selected-colour-name");
	function applyVariant() {
		var cat = COMBO[selColour + "|" + selSize];
		if (!cat || !VARIANTS[cat]) { return; }
		var v = VARIANTS[cat];
		renderGallery(v.gallery);
		window.MERCH_CURRENT_VARIANT = v.sync;
		var sb = document.getElementById("stripe-pay-button");
		if (sb) { sb.setAttribute("data-variant", v.sync); }
	}
	function refreshSizes() {
		sizeBtns.forEach(function (b) {
			var sz = b.getAttribute("data-size");
			var ok = !!COMBO[selColour + "|" + sz];
			b.disabled = !ok;
			b.classList.toggle("is-active", ok && sz === selSize);
		});
	}
	swatches.forEach(function (sw) {
		sw.addEventListener("click", function () {
			selColour = sw.getAttribute("data-colour");
			swatches.forEach(function (x) { x.classList.remove("is-active"); });
			sw.classList.add("is-active");
			if (colourNameEl) { colourNameEl.textContent = selColour; }
			if (!COMBO[selColour + "|" + selSize]) {
				for (var i = 0; i < sizeBtns.length; i++) {
					var sz = sizeBtns[i].getAttribute("data-size");
					if (COMBO[selColour + "|" + sz]) { selSize = sz; break; }
				}
			}
			refreshSizes();
			applyVariant();
		});
	});
	sizeBtns.forEach(function (b) {
		b.addEventListener("click", function () {
			if (b.disabled) { return; }
			selSize = b.getAttribute("data-size");
			refreshSizes();
			applyVariant();
		});
	});
	refreshSizes();
})();
</script>
<?php } ?>
