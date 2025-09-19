<?php
require('../template/top.php');
head('Dues Paid', true);
$userinfo = auth();

//$log = var_export($_REQUEST, true);
//error_log($log, 3, BASE . '/paypal/logs/pdt-dues.log');
?>

<main class="page-content">
        <section class="section-50 section-md-75 section-lg-100">
        	<div class="shell">
        		<div class="range range-md-justify">
        			<div class="cell-md-12">
        				<div class="inset-md-right-30 inset-lg-right-0 text-center">
        					<h1>Dues Paid</h1>

						<p><strong>Thank you for paying your dues.</strong></p>
							<?php
                            // give the join Discord link if the user has already verified their UNT email
							if(isset($userinfo['unt_email'])) {
							?>
						<a href="/join/w/discord">Click here to update your Discord account status.</a>
							<?php
							}
							else { // show form to verify UNT email
							?>
						<section id="email-verification-section" class="range range-vertical range-xs-middle text-left">
							<div style="max-width: 50%">
								<h6>Next, please verify your UNT email address.</h6>
							</div>
							<form id="verify-email" data-type="email" data-form-type="verify-email" data-form-output="form-output-global" method="post" action="/ajax/verify-unt-email.php" style="min-width: 30%">
								<div class="form-group postfix-xl-right-40 offset-top-40">
									<label for="email" class="form-label rd-input-label">E-mail <em>(@my.unt.edu)</em></label>
									<input id="email" type="email" name="email" data-constraints="@Email @Required" class="form-control form-control-has-validation form-control-last-child">
								</div>
								<button type="submit" style="min-width: 100%" class="btn btn-form btn-default offset-top-20">Send message</button>
							</form>
						</section>
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
footer();
?>
