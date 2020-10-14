<?php
require('../template/top.php');
require(BASE . '/template/functions/payment_button.php');
head('Pay Dues', true);

$q = $db->query("SELECT `value` FROM dues_config WHERE `key` = 'semester_price'");
if (!$q || $q->num_rows !== 1) {
	// TODO: Uh oh!
}

$dues_price = $q->fetch_row()[0];
?>

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

					<p>Please use the Pay Now button to pay your dues via PayPal.</p>
					<p style="margin-top: 0px;">Once you have paid, your Discord account will automatically be given the <em>Good Standing</em> role.</p>

					<p><strong style="font-size: 20px;"><pre style="display: inline-block;border-radius: 10px;">Cost: $<?php echo $dues_price; ?></pre></strong></p>

					<div style="display: inline-block;">
						<?php
							$custom = serialize(array(
								'source' => 'DUES_PAYMENT',
								'uid' => $userinfo['id']
							));

							$payment_button = new PaymentButton(
								'UNT Robotics Dues',
								$dues_price,
								'Pay Now',
								'Complete Dues Payment'
							);

							$payment_button->set_custom($custom);
							$payment_button->set_opt_names(
								array(
									'Semester',
									'Year'
								)
							);
							$payment_button->set_opt_vals(
								array(
									Semester::get_name_from_value($untrobotics->get_current_term()),
									$untrobotics->get_current_year()
								)
							);
							$payment_button->set_complete_return_uri('/dues/paid');

							$button = $payment_button->get_button();
							if ($button->error === false) {
								echo $payment_button->get_button()->button;
							} else {
								// TODO: Alert
								?>
						<div class="alert alert-danger">An error occurred loading the payment button...</div>
								<?php
							}
						?>
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

<script>
	$('input[src="https://www.paypalobjects.com/en_US/i/btn/btn_paynowCC_LG.gif"]').on('click', function(e) {
		return true;
		if (!$('input[name="os0"]').val() || !$('input[name="os1"]').val() || !$('input[name="os2"]').val()) {
			alert('Please fill out your name, EUID & e-mail address.');
			e.preventDefault();
			return false;
		}
	});
</script>