<?php
require('../../template/top.php');
head('T-Shirt Ordered', true);

$log = var_export($_REQUEST, true);
$log .= var_export($userinfo, true);
error_log($log, 0, 'pdt.log');

?>

<main class="page-content">
        <section class="section-50">
          <div class="shell">
            <div class="range range-md-justify">
              <div class="cell-md-12">
                <div class="inset-md-right-30 inset-lg-right-0 text-center">
                  <h1>T-Shirt Ordered!</h1>

					<p><strong>Thank you for your order.</strong></p>
					
				  </div>
				</div>
			  </div>
			</div>
	</section>
</main>

<?php
footer();
?>