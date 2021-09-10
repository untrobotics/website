<?php
require('../template/top.php');
require(BASE . '/template/functions/payment_button.php');
head('Pay Dues', true);

$q = $db->query("SELECT `value` FROM dues_config WHERE `key` = 'semester_price'");
if (!$q || $q->num_rows !== 1) {
    AdminBot::send_message("Unable to determine the dues payment price");
	throw new RuntimeException("Unable to determine dues payment price");
}

$single_semester_dues_price = $q->fetch_row()[0];
$full_year_dues_price = $single_semester_dues_price * 2;
$current_term = $untrobotics->get_current_term();
$next_term = $untrobotics->get_next_term();

// only allow the user to pay for the full year it is the autumn semester
$permit_full_year_payment = $current_term == Semester::AUTUMN;
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
                            </div>
                        <?php
                        }
                        ?>

                        <p><strong style="font-size: 20px;"><pre style="display: inline-block;border-radius: 10px;">Cost: <span id="dues_cost">$<?php echo $single_semester_dues_price; ?></span></pre></strong></p>

                    <?php
                    // both semesters
                    ?>
                    <div class="dues-payment-button two-semesters">
                        <?php
                        $custom = serialize(array(
                            'source' => 'DUES_PAYMENT',
                            'uid' => $userinfo['id']
                        ));

                        $payment_button = new PaymentButton(
                            'UNT Robotics Dues',
                            $full_year_dues_price,
                            'Pay Now',
                            'Complete Dues Payment'
                        );

                        $payment_button->set_custom($custom);
                        $payment_button->set_opt_names(
                            array(
                                'Semester',
                                'Year',
                                'Semester1',
                                'Year1'
                            )
                        );
                        $payment_button->set_opt_vals(
                            array(
                                Semester::get_name_from_value($current_term),
                                $untrobotics->get_current_year(),
                                Semester::get_name_from_value($next_term),
                                $untrobotics->get_next_year()
                            )
                        );
                        $payment_button->set_complete_return_uri('/dues/paid');

                        $button = $payment_button->get_button();
                        if ($button->error === false) {
                            echo $payment_button->get_button()->button;
                        } else {
                            AdminBot::send_message("Failed to load dues payment button: " . $button->error);
                            ?>
                            <div class="alert alert-danger">An error occurred loading the payment button...</div>
                            <?php
                        }
                        ?>
                    </div>

                    <?php
                    // single semester
                    ?>
					<div class="dues-payment-button one-semesters">
						<?php
							$custom = serialize(array(
								'source' => 'DUES_PAYMENT',
								'uid' => $userinfo['id']
							));

							$payment_button = new PaymentButton(
								'UNT Robotics Dues',
                                $single_semester_dues_price,
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
									Semester::get_name_from_value($current_term),
									$untrobotics->get_current_year()
								)
							);
							$payment_button->set_complete_return_uri('/dues/paid');

							$button = $payment_button->get_button();
							if ($button->error === false) {
								echo $payment_button->get_button()->button;
							} else {
                                AdminBot::send_message("Failed to load dues payment button: " . $button->error);
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
    const single_semester_price = <?php echo intval($single_semester_dues_price); ?>;
    const full_semester_price = <?php echo intval($full_year_dues_price); ?>;

	$('input[name="full-year"]').on("change", function(e) {
	   if ($(this).is(':checked')) {
	       $("#dues_cost").text("$" + full_semester_price);
           $(".dues-payment-button.two-semesters").css('display','inline-block');
           $(".dues-payment-button.one-semesters").css('display','none');
       } else {
           $("#dues_cost").text("$" + single_semester_price);
           $(".dues-payment-button.two-semesters").css('display','none');
           $(".dues-payment-button.one-semesters").css('display','inline-block');
       }
    });
</script>