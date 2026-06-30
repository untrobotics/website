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
</style>

<main class="page-content">
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

					<div class="dues-payment-button"></div>

                    <div class="offset-top-10">
                        <button id="stripe-pay-button" type="button" class="btn btn-primary">Pay with Card / Apple Pay</button>
                    </div>

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
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars($paypal_client_id); ?>&currency=USD"></script>
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
    });

	$('#include-tshirt').on('change', function(e) {
	    tShirt = e.target.value || null;
        $("#dues_cost").text("$" + getDuesCost());
    })

    // Stripe Checkout (Card / Apple Pay). The server recomputes the price; we
    // only send which options were chosen.
    $('#stripe-pay-button').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).text('Redirecting...');
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
                    $btn.prop('disabled', false).text('Pay with Card / Apple Pay');
                }
            })
            .catch(() => {
                alert('Unable to start checkout. Please try again.');
                $btn.prop('disabled', false).text('Pay with Card / Apple Pay');
            });
    });
</script>