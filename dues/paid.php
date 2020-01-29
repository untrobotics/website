<?php
require('../template/top.php');
head('Dues Paid', true);


$log = var_export($_REQUEST, true);
error_log($log, 0, 'paypal/pdt.log');

?>

<main class="page-content">
        <section class="section-75 section-md-100 section-lg-150">
        	<div class="shell">
        		<div class="range range-md-justify">
        			<div class="cell-md-12">
        				<div class="inset-md-right-30 inset-lg-right-0 text-center">
        					<h1>Dues Paid</h1>

						<p><strong>Thank you for paying your dues.</strong></p>
					</div>
				</div>
			</div>
		</div>
	</section>
</main>

<?php
footer();
?>
