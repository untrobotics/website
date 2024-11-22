<?php
require('../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
head('Pay Dues via Alternatives', true, false, false, "If you paid for your dues not using PayPal, fill out the form on this page to get your Good Standing role.");

if (isset($_POST['submit'])) {
    AdminBot::send_message("Nick said this was okay..." . var_export(array($_POST, $userinfo['id'], $userinfo['name']), true));

    $authorise_user = true;
}

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
</style>

<main class="page-content">
	<section class="section-50 section-md-75 section-lg-100">
	  <div class="shell">
		<div class="range range-md-justify">
		  <div class="cell-md-12">
			<div class="inset-md-right-30 inset-lg-right-0 text-center">

			  <h1>Pay Dues</h1>
                <form action="" method="POST">
				<?php
				if (is_current_user_authenticated()) {

				    if (@$authorise_user) {
				        ?>
                        <p>Thank you for your submission! Now please make sure your Discord account is linked with your UNT Robotics account by clicking here:</p>
                        <a href="/join/w/discord">Click here to update your Discord account status.</a>

                        <?php
                    } else if (!$untrobotics->is_user_in_good_standing($userinfo)) {
						?>

					<p>Dues can be paid via alternative methods, or you may request an exemption from paying dues each semester due to your circumstances.</p>
                    <p style="margin-top: 0px;">Please select the reason below why your are requesting an alternative dues payment. This will be sent to our leadership for approval, so please make sure to communicate with an officer before submitting this form. If you are requesting an exemption, please reach out to our treasurer at <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a></p>

                    <div class="row offset-top-10">
                        <div class="col-lg-offset-4 col-lg-4 col-sm-12">
                            <select id="alternative_payment_reason" name="alternative_payment_reason" class="">
                                <option>Select reason...</option>
                                <option value="paid-in-person">Paid in Person</option>
                                <option value="circumstances">Extenuating circumstances</option>
                                <option value="nasa-sl-volunteer">Senior Design NASA SL Team</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="offset-top-20">
                        <div class="form-group">
                            <label class="checkbox-container"> I have spoken to an officer already
                                <input autocomplete="off" name="confirmation" type="checkbox" class="form-control form-control-has-validation form-control-last-child checkbox-custom" value="1"><span class="checkbox-custom-dummy"></span>
                                <span class="checkmark"></span>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-default offset-top-35" name="submit" value="submit">Submit</button>

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
                </form>

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
</script>