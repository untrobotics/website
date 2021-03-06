<?php
require('../template/top.php');
head('Dues Paid', true);

$log = var_export($_REQUEST, true);
error_log($log, 3, BASE . '/paypal/logs/pdt-dues.log');
?>

<main class="page-content">
        <section class="section-50 section-md-75 section-lg-100">
        	<div class="shell">
        		<div class="range range-md-justify">
        			<div class="cell-md-12">
        				<div class="inset-md-right-30 inset-lg-right-0 text-center">
        					<h1>Dues Paid</h1>

						<p><strong>Thank you for paying your dues.</strong></p>
							
						<a href="/join/w/discord">Click here to update your Discord account status.</a>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
footer();
?>
