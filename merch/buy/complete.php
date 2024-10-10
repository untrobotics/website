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

				<!--	<div class="payment-information offset-top-20">
						<h5>Order Information</h5>
						<div class="payment-information-container">
							<ul>
								<li><strong>PayPal TX ID</strong>: <?php /*echo $payment['id']; */?></li>
								<li><strong>Payment Amount</strong>: $<?php /*echo $payment['amount']['value']; */?></li>
								<li><strong>Product Type</strong>: <?php /*echo $pdt->option_selection1; */?></li>
								<li><strong>Product Name</strong>: <?php /*echo $pdt->option_selection2; */?></li>
								<li><strong>Product Variant</strong>: <?php /*echo $pdt->option_selection3; */?></li>
								<li><strong>Quantity</strong>: <?php /*echo $pdt->quantity; */?></li>
							</ul>
						</div>
						<div></div>
						<h5 class="offset-top-10">Shipping Address</h5>
						<div class="payment-information-container">
							<p><strong><?php /*echo $pdt->address_name; */?></strong></p>
							<p><?php /*echo $pdt->address_street; */?></p>
							<p><?php /*echo $pdt->address_city; */?>, <?php /*echo $pdt->address_state; */?> <?php /*echo $pdt->address_zip; */?></p>
							<p><?php /*echo $pdt->address_country_code; */?></p>
						</div>
					</div>-->
				  </div>
				</div>
			  </div>
			</div>
	</section>
</main>

<?php
footer();
?>