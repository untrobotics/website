<?php
require('../../template/top.php');
head('Merch Ordered', true);

//if(isset($_GET['token'])){
//    require_once('../../api/paypal/paypal.php');
//    $p = new PayPalCustomApi();
//    $order = $p->get_order_info($_GET['token']);
//} else{
//    $order = false;
//}
?>
<style>
	.payment-information {
		display: inline-flex;
    	flex-direction: column;
	    max-width: 400px;
	}
	.payment-information > div.payment-information-container {
		text-align: left;
    	display: inline-block;
	}
	.payment-information > div.payment-information-container p {
		margin-top: 0;
	}
</style>
<main class="page-content">
        <section class="section-50">
          <div class="shell">
            <div class="range range-md-justify">
              <div class="cell-md-12">
                <div class="inset-md-right-30 inset-lg-right-0 text-center">
                  <h1>Merch Ordered!</h1>
					<h4>Thank you for your order.</h4>
					<div>
						<p>You should receive an e-mail in a few minutes with your payment receipt.</p>
					</div>
				  </div>
				</div>
			  </div>
			</div>
	</section>
</main>

<?php
footer();
?>