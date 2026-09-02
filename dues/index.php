<?php
require('../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
head('Pay Dues', true);

$q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
if (!$q || $q->num_rows !== 2) {
    AdminBot::send_message("Unable to determine the dues payment price");
	throw new RuntimeException("Unable to determine dues payment price");
}
$r = $q->fetch_all(MYSQLI_ASSOC);

$mapped_config = array();
array_walk(
    $r,
    function(&$val, $_key) use (&$mapped_config)
    {
        $mapped_config[$val['key']] = $val['value'];
    }
);

$t_shirt_dues_purchase_price = $mapped_config['t_shirt_dues_purchase_price'];
$single_semester_dues_price = $mapped_config['semester_price'];
$full_year_dues_price = $single_semester_dues_price * 2;
$current_term = $untrobotics->get_current_term();
$next_term = $untrobotics->get_next_term();

// only allow the user to pay for the full year it is the autumn semester
$permit_full_year_payment = $current_term == Semester::AUTUMN;

// PayPal JS SDK client id for the active environment (sandbox flag is set by the
// auth() call inside head() for sandbox users; guests/live get the live id).
$paypal_client_id = $untrobotics->get_sandbox() ? PAYPAL_SANDBOX_CLIENT_ID : PAYPAL_CLIENT_ID;
?>

<style>
    label.checkbox-container {
        display: inline-block;
    }
    .dues-payment-button {
        display: inline-block;
    }
    .dues-payment-button.two-semesters {
        display: none;
    }
    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
        color: #686868;
        padding: 18px;
    }

    label.checkbox-container {
        display: inline-block;
    }
    .dues-payment-button {
        display: inline-block;
    }
    .dues-payment-button.two-semesters {
        display: none;
    }
    .dues-shirt-preview {
        display: inline-block;
        margin: 0 auto;
    }
    .dues-shirt-preview img {
        width: 300px;
    }
    /* Payment buttons all share one centred column width so they tessellate. */
    #express-checkout-element:empty { display: none; }
    .dues-payment-button { display: block !important; width: 100%; max-width: 400px; margin: 0 auto 10px; }
    #stripe-pay-button { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; max-width: 400px; margin: 0 auto; height: 46px; border: 0; border-radius: 6px; background: #635bff; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; }
    .stripe-note { text-align: center; font-size: 11px; color: #9aa0a6; margin: 6px auto 0; max-width: 400px; }
    #stripe-pay-button:hover { background: #544dff; }
    #applepay-redirect { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; max-width: 400px; height: 46px; margin: 0 auto 10px; border: 0; border-radius: 6px; background: #000; color: #fff; font-size: 18px; font-weight: 500; cursor: pointer; }
    /* Uniform vertical spacing across every payment button. */
    #express-checkout-element, #applepay-redirect, .dues-payment-button, #stripe-pay-button { margin-bottom: 10px !important; }
    #express-checkout-element:empty { margin-bottom: 0 !important; }
    /* PayPal reserves ~8px below its buttons inside its container; trim its bottom
       margin so the gap below PayPal matches the 10px between the other buttons. */
    .dues-payment-button { margin-bottom: 2px !important; }
        .stripe-mark { display: inline-flex; align-items: center; padding: 3px 7px; border-radius: 4px; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.32); }
    </style>

<main class="page-content">
	<section class="breadcrumb-classic">
	  <div class="rd-parallax">
	    <div data-speed="0.25" data-type="media" data-url="/images/headers/dues.jpg" class="rd-parallax-layer"></div>
	    <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
	      <div class="shell">
	        <ul class="list-breadcrumb">
	          <li><a href="/">Home</a></li>
	          <li>Pay Dues</li>
	        </ul>
	      </div>
	    </div>
	  </div>
	</section>
	<section class="section-50 section-md-75 section-lg-100">
	  <div class="shell">
		<div class="range range-md-justify">
		  <div class="cell-md-12">
			<div class="inset-md-right-30 inset-lg-right-0 text-center">
			  <h1>Pay Dues</h1>
				<?php
				if (is_current_user_authenticated()) {
					if (!$untrobotics->is_user_in_good_standing($userinfo)) {
						?>

					<p>Please use the Pay Now button to pay your dues via PayPal. Dues are per semester, however you can choose to pay for the whole year at once.</p>
					<p style="margin-top: 0px;">Once you have paid, your Discord account will automatically be given the <em>Good Standing</em> role.</p>

                        <?php
                            if ($permit_full_year_payment) {
                        ?>
                            <div class="offset-top-20">
                                <div class="form-group">
                                    <label class="checkbox-container"> Pay for both Spring &amp; Fall?
                                        <input autocomplete="off" name="full-year" type="checkbox" class="form-control form-control-has-validation form-control-last-child checkbox-custom" value="1"><span class="checkbox-custom-dummy"></span>
                                        <span class="checkmark"></span>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="checkbox-container"> <div>Order a T-shirt with your dues? (50% off!)</div>
                                        <div><small>(The shirt will be shipped to the shipping address you select during payment)</small></div>
                                        <div class="dues-shirt-preview">
                                            <a href="/images/dues-shirt.png" target="_blank">
                                                <img src="/images/dues-shirt.png"/>
                                            </a>
                                        </div>
                                        <select id="include-tshirt" name="include-tshirt" class="">
                                            <option value="" selected="selected">No T-shirt</option>
                                            <option value="632b8e41a865f1">XS</option>
                                            <option value="632b8e41a86664">S</option>
                                            <option value="632b8e41a866a1">M</option>
                                            <option value="632b8e41a866e2">L</option>
                                            <option value="632b8e41a86724">XL</option>
                                            <option value="632b8e41a86761">2XL</option>
                                            <option value="632b8e41a867a9">3XL</option>
                                            <option value="632b8e41a867e6">4XL</option>
                                        </select>
                                    </label>
                                </div>
                            </div>
                        <?php
                        }
                        ?>

                        <p><strong style="font-size: 20px;"><pre style="display: inline-block;border-radius: 10px;">Cost: <span id="dues_cost">$<?php echo $single_semester_dues_price; ?></span></pre></strong></p>

					<div id="express-checkout-element" style="max-width:400px;margin:0 auto 10px;"></div>
                        <button id="applepay-redirect" type="button" style="max-width:400px;margin:0 auto 10px;"><svg width="16" height="20" viewBox="0 0 384 512" fill="currentColor" aria-hidden="true"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C60.7 141.5 0 184.1 0 270c0 25.4 4.6 51.6 13.9 78.6 12.5 35.5 57.5 122.6 104.4 121.2 24.6-.6 42-17.4 74-17.4 31.1 0 47.3 17.4 74.7 17.4 47.4-.7 88-79.7 100-115.3-63.5-30-62.3-87.6-62.3-89.8zm-51.7-165c25-29.7 22.7-56.7 22-66.5-22.1 1.3-47.6 15-62.2 32.9-16 19.2-25.4 42.9-23.4 66 23.9 1.8 45.7-10.5 63.6-32.4z"/></svg> Pay</button>
					<div class="dues-payment-button"></div>

                    <button id="stripe-pay-button" type="button"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg><span class="pay-label">Pay with card</span><svg class="stripe-mark" width="40" height="17" viewBox="0 0 468 222.5" fill="currentColor" aria-hidden="true"><path d="M414 113.4c0-25.6-12.4-45.8-36.1-45.8-23.8 0-38.2 20.2-38.2 45.6 0 30.1 17 45.3 41.4 45.3 11.9 0 20.9-2.7 27.7-6.5v-20c-6.8 3.4-14.6 5.5-24.5 5.5-9.7 0-18.3-3.4-19.4-15.2h48.9c0-1.3.2-6.5.2-8.9zm-49.4-9.5c0-11.3 6.9-16 13.2-16 6.1 0 12.6 4.7 12.6 16h-25.8zM301.1 67.6c-9.8 0-16.1 4.6-19.6 7.8l-1.3-6.2h-22v116.6l25-5.3.1-28.3c3.6 2.6 8.9 6.3 17.7 6.3 17.9 0 34.2-14.4 34.2-46.1-.1-29-16.6-44.8-34.1-44.8zm-6 68.9c-5.9 0-9.4-2.1-11.8-4.7l-.1-37.1c2.6-2.9 6.2-4.9 11.9-4.9 9.1 0 15.4 10.2 15.4 23.3 0 13.4-6.2 23.4-15.4 23.4zM223.8 61.7l25.1-5.4V36l-25.1 5.3v20.4zM223.8 69.3h25.1v87.5h-25.1zM196.9 76.7l-1.6-7.4h-21.6v87.5h25V97.8c5.9-7.7 15.9-6.3 19-5.2v-23c-3.2-1.2-14.9-3.4-20.8 7.1zM146.9 47.6l-24.4 5.2-.1 80.1c0 14.8 11.1 25.7 25.9 25.7 8.2 0 14.2-1.5 17.5-3.3V135c-3.2 1.3-19 5.9-19-8.9V90.6h19V69.3h-19l.1-21.7zM79.3 94.7c0-3.9 3.2-5.4 8.5-5.4 7.6 0 17.2 2.3 24.8 6.4V72.2c-8.3-3.3-16.5-4.6-24.8-4.6C67.5 67.6 54 78.2 54 95.9c0 27.6 38 23.2 38 35.1 0 4.6-4 6.1-9.6 6.1-8.3 0-18.9-3.4-27.3-8v23.8c9.3 4 18.7 5.7 27.3 5.7 20.8 0 35.1-10.3 35.1-28.2-.1-29.8-38.2-24.5-38.2-35.7z"/></svg></button>
                    <p class="offset-top-20" style="font-size:14px;"><a href="/dues/alternatives">Can’t pay online? Request an alternative or exemption →</a></p>

					<?php
					} else {
						?>
						<div class="alert alert-info alert-inline">You have already paid your dues for this semester. :&#41;</div>
						<?php
					}
				} else {
					?>
					<div class="alert alert-info alert-inline">You must <a href="/auth/login?returnto=<?php echo urlencode('/dues'); ?>">log in</a> to pay dues.</div>
					<?php
				}
				?>
				
			  </div>
			</div>
		  </div>
		</div>
	</section>
</main>

<?php
footer(false);
?>

<script src="https://js.stripe.com/v3/"></script>
<?php if (is_current_user_authenticated() && !$untrobotics->is_user_in_good_standing($userinfo)) { ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars($paypal_client_id); ?>&currency=USD&disable-funding=card"></script>
<?php } ?>
<script>
    const single_semester_price = <?php echo intval($single_semester_dues_price); ?>;
    const full_semester_price = <?php echo intval($full_year_dues_price); ?>;
    const t_shirt_price = <?php echo intval($t_shirt_dues_purchase_price); ?>;

    let fullYear = false;
    let tShirt = null;

    function getDuesCost() {
        let cost = 0;
        if (fullYear) {
            cost += full_semester_price;
        } else {
            cost += single_semester_price
        }
        if (tShirt) {
            cost += t_shirt_price;
        }
        return cost;
    }

    // Express Checkout Element: native Apple Pay / Google Pay / Link. Amount and
    // shipping (only when a T-shirt is added) follow the current options; the
    // Element is rebuilt when the shipping requirement flips.
    var __stripe = window.Stripe ? Stripe('<?php echo htmlspecialchars(STRIPE_PUBLISHABLE_KEY, ENT_QUOTES); ?>') : null;
    var __elements = null, __ece = null, __eceShip = null;
    function updateEceAmount() { if (__elements) { __elements.update({ amount: Math.round(getDuesCost() * 100) || 100 }); } }
    function buildEce() {
        var host = document.getElementById('express-checkout-element');
        if (!__stripe || !host) { return; }
        var needShip = !!tShirt;
        var cents = Math.round(getDuesCost() * 100) || 100;
        if (__ece && __eceShip === needShip) { __elements.update({ amount: cents }); return; }
        if (__ece) { __ece.unmount(); }
        __elements = __stripe.elements({ mode: 'payment', amount: cents, currency: 'usd' });
        var opts = { emailRequired: true, paymentMethods: { applePay: 'auto', googlePay: 'auto', link: 'never', amazonPay: 'never', paypal: 'never', klarna: 'never' } };
        if (needShip) { opts.shippingAddressRequired = true; opts.allowedShippingCountries = ['US']; opts.shippingRates = [{ id: 'free', displayName: 'Free shipping', amount: 0 }]; }
        __ece = __elements.create('expressCheckout', opts);
        __ece.mount('#express-checkout-element');
        var apRedirect = document.getElementById('applepay-redirect');
        // Decide by ACTUAL rendered height, not availablePaymentMethods: Stripe can
        // report a method then mount an empty 0-height iframe that keeps eating a flex
        // gap on both sides. Empty -> collapse + show the hosted fallback; rendered ->
        // hide the fallback. The timer covers the case where 'ready' never fires.
        function settleWallet() {
            var eceEl = document.getElementById('express-checkout-element');
            if (!eceEl || eceEl.dataset.settled) { return; }
            if (eceEl.getBoundingClientRect().height > 8) {
                if (apRedirect) { apRedirect.style.display = 'none'; }
            } else {
                eceEl.style.display = 'none';
                if (apRedirect) { apRedirect.style.display = 'flex'; }
            }
            eceEl.dataset.settled = '1';
        }
        __ece.on('ready', function () { setTimeout(settleWallet, 350); });
        setTimeout(settleWallet, 2000);
        if (needShip) { __ece.on('shippingaddresschange', function (e) { e.resolve(); }); }
        __ece.on('confirm', function (event) {
            __elements.submit().then(function (sub) {
                if (sub.error) { alert(sub.error.message); return; }
                var params = new URLSearchParams();
                params.set('source', 'dues');
                params.set('full-year', fullYear ? 'true' : 'false');
                params.set('t-shirt', tShirt || '');
                params.set('email', (event.billingDetails && event.billingDetails.email) || '');
                return fetch('/api/stripe/create-payment-intent.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params.toString(), credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d || !d.clientSecret) { alert((d && d.error) || 'Unable to start payment.'); return; }
                        var confirmParams = { return_url: location.origin + '/dues/paid' };
                        var sa = event.shippingAddress;
                        if (sa && sa.address) {
                            confirmParams.shipping = { name: sa.name || '', address: { line1: sa.address.line1 || '', line2: sa.address.line2 || '', city: sa.address.city || '', state: sa.address.state || '', postal_code: sa.address.postal_code || '', country: sa.address.country || 'US' } };
                        }
                        return __stripe.confirmPayment({ elements: __elements, clientSecret: d.clientSecret, confirmParams: confirmParams }).then(function (res) { if (res.error) { alert(res.error.message); } });
                    });
            }).catch(function () { alert('Unable to start payment.'); });
        });
        __eceShip = needShip;
    }
    buildEce();
    (function () {
        var apRedirect = document.getElementById('applepay-redirect');
        if (!apRedirect) { return; }
        apRedirect.addEventListener('click', function () {
            apRedirect.disabled = true;
            var rp = new URLSearchParams();
            rp.set('source', 'dues');
            rp.set('full-year', fullYear ? 'true' : 'false');
            rp.set('t-shirt', tShirt || '');
            fetch('/api/stripe/create-checkout-session', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: rp.toString(), credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (d && d.url) { window.location = d.url; } else { apRedirect.disabled = false; alert((d && d.error) || 'Unable to start checkout.'); } })
                .catch(function () { apRedirect.disabled = false; alert('Unable to start checkout.'); });
        });
    })();

    // PayPal Smart Buttons (Orders v2). The button is rendered once; createOrder
    // reads the current options at click time and the SERVER recomputes the price
    // — the client never sends an amount.
    if (window.paypal) {
        paypal.Buttons({
            createOrder: function() {
                const params = new URLSearchParams();
                params.set('source', 'dues');
                params.set('full-year', fullYear ? 'true' : 'false');
                params.set('t-shirt', tShirt || '');
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
                            window.location = '/dues/paid';
                        } else {
                            alert((d && d.error) ? d.error : 'Your payment could not be completed.');
                        }
                    });
            },
            onError: function(err) {
                alert('An error occurred with PayPal checkout. Please try again.');
            }
        }).render('.dues-payment-button');
    }

	$('input[name="full-year"]').on("change", function(e) {
        fullYear = !!$(this).is(':checked');
        $("#dues_cost").text("$" + getDuesCost());
        updateEceAmount();
    });

	$('#include-tshirt').on('change', function(e) {
	    tShirt = e.target.value || null;
        $("#dues_cost").text("$" + getDuesCost());
        buildEce();
    })

    // Stripe Checkout (Card). The server recomputes the price; we
    // only send which options were chosen.
    $('#stripe-pay-button').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).find('.pay-label').text('Redirecting...');
        const params = new URLSearchParams();
        params.set('source', 'dues');
        params.set('full-year', fullYear ? 'true' : 'false');
        params.set('t-shirt', tShirt || '');
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
                    $btn.prop('disabled', false).find('.pay-label').text('Pay with card');
                }
            })
            .catch(() => {
                alert('Unable to start checkout. Please try again.');
                $btn.prop('disabled', false).find('.pay-label').text('Pay with card');
            });
    });
</script>