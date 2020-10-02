<?php
require('../../template/top.php');
head('Merch Ordered', true);

$log = var_export($_REQUEST, true);
$log .= var_export($userinfo, true);
error_log($log, 3, './pdt.log');

function validate_pdt($tx) {
	global $untrobotics;

	// read the post from PayPal system and add 'cmd'
	$req = 'cmd=_notify-synch';
	$req .= "&tx=$tx&at=";

	$ch = curl_init();
	if ($untrobotics->get_sandbox()) {
		$hostname = 'www.sandbox.paypal.com';
		curl_setopt($ch, CURLOPT_URL, "https://{$hostname}/cgi-bin/webscr");
		$req .= PAYPAL_SANDBOX_PDT_ID_TOKEN;
	} else {
		$hostname = 'www.paypal.com';
		curl_setopt($ch, CURLOPT_URL, "https://{$hostname}/cgi-bin/webscr");
		$req .= PAYPAL_PDT_ID_TOKEN;
	}
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
	//set cacert.pem verisign certificate path in curl using 'CURLOPT_CAINFO' field here
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Host: {$hostname}"));
	$result = curl_exec($ch);
	curl_close($ch);
	
	if(!$result){
		return false;
	} else {
		// parse the data
		$lines = explode("\n", trim($result));
		$keyarray = array();
		
		if (strcmp ($lines[0], "SUCCESS") == 0) {
			for ($i = 1; $i < count($lines); $i++) {
				$temp = explode("=", $lines[$i],2);
				$keyarray[urldecode($temp[0])] = urldecode($temp[1]);
			}
			
			// check the payment_status is Completed
			$pdt = new PayPalPDT($keyarray);
			
			return $pdt;
			
		} else if (strcmp ($lines[0], "FAIL") == 0) {
			return false;
		}
	}
}

class PayPalPDT {
  public $payer_email; //' => 'unt.robotics-buyer@unt.edu',
  public $payer_id; //' => 'XKVTP2Y84G8ZU',
  public $payer_status; //' => 'VERIFIED',
  public $first_name; //' => 'test',
  public $last_name; //' => 'buyer',
  public $address_name; //' => 'test buyer',
  public $address_street; //' => '1 Main St',
  public $address_city; //' => 'San Jose',
  public $address_state; //' => 'CA',
  public $address_country_code; //' => 'US',
  public $address_zip; //' => '95131',
  public $residence_country; //' => 'US',
  public $txn_id; //' => '5DU97498HP117113U',
  public $mc_currency; //' => 'USD',
  public $mc_fee; //' => '1.03',
  public $mc_gross; //' => '25.00',
  public $protection_eligibility; //' => 'ELIGIBLE',
  public $payment_fee; //' => '1.03',
  public $payment_gross; //' => '25.00',
  public $payment_status; //' => 'Completed',
  public $payment_type; //' => 'instant',
  public $handling_amount; //' => '0.00',
  public $shipping; //' => '0.00',
  public $item_name; //' => 'UNT Robotics Baseball Cap',
  public $quantity; //' => '1',
  public $txn_type; //' => 'cart',
  public $option_name1; //' => 'Type',
  public $option_selection1; //' => 'Hat',
  public $option_name2; //' => 'Product',
  public $option_selection2; //' => 'UNT Robotics Baseball Cap',
  public $option_name3; //' => 'Variant',
  public $option_selection3; //' => 'Spruce',
  public $payment_date; //' => '2020-09-22T22:28:21Z',
  public $business; //' => 'unt.robotics-facilitator@unt.edu',
  public $receiver_id; //' => '8XWRKXHDFG8AW',
  public $custom; //' => 'a:3:{s:6:"source";s:16:"PRINTFUL_PRODUCT";s:7:"product";s:14:"5f5a8f842802b1";s:7:"variant";i:2135594502;}',
	
	public function __construct($object) {
		$this->payer_email = $object['payer_email']; //' => 'unt.robotics-buyer@unt.edu',
		$this->payer_id = $object['payer_id']; //' => 'XKVTP2Y84G8ZU',
		$this->payer_status = $object['payer_status']; //' => 'VERIFIED',
		$this->first_name = $object['first_name']; //' => 'test',
		$this->last_name = $object['last_name']; //' => 'buyer',
		$this->address_name = $object['address_name']; //' => 'test buyer',
		$this->address_street = $object['address_street']; //' => '1 Main St',
		$this->address_city = $object['address_city']; //' => 'San Jose',
		$this->address_state = $object['address_state']; //' => 'CA',
		$this->address_country_code = $object['address_country_code']; //' => 'US',
		$this->address_zip = $object['address_zip']; //' => '95131',
		$this->residence_country = $object['residence_country']; //' => 'US',
		$this->txn_id = $object['txn_id']; //' => '5DU97498HP117113U',
		$this->mc_currency = $object['mc_currency']; //' => 'USD',
		$this->mc_fee = $object['mc_fee']; //' => '1.03',
		$this->mc_gross = $object['mc_gross']; //' => '25.00',
		$this->protection_eligibility = $object['protection_eligibility']; //' => 'ELIGIBLE',
		$this->payment_fee = $object['payment_fee']; //' => '1.03',
		$this->payment_gross = $object['payment_gross']; //' => '25.00',
		$this->payment_status = $object['payment_status']; //' => 'Completed',
		$this->payment_type = $object['payment_type']; //' => 'instant',
		$this->handling_amount = $object['handling_amount']; //' => '0.00',
		$this->shipping = $object['shipping']; //' => '0.00',
		$this->item_name = $object['item_name']; //' => 'UNT Robotics Baseball Cap',
		$this->quantity = $object['quantity']; //' => '1',
		$this->txn_type = $object['txn_type']; //' => 'cart',
		$this->option_name1 = $object['option_name1']; //' => 'Type',
		$this->option_selection1 = $object['option_selection1']; //' => 'Hat',
		$this->option_name2 = $object['option_name2']; //' => 'Product',
		$this->option_selection2 = $object['option_selection2']; //' => 'UNT Robotics Baseball Cap',
		$this->option_name3 = $object['option_name3']; //' => 'Variant',
		$this->option_selection3 = $object['option_selection3']; //' => 'Spruce',
		$this->payment_date = $object['payment_date']; //' => '2020-09-22T22:28:21Z',
		$this->business = $object['business']; //' => 'unt.robotics-facilitator@unt.edu',
		$this->receiver_id = $object['receiver_id']; //' => '8XWRKXHDFG8AW',
		$this->custom = $object['custom']; //' => 'a:3:{s:6:"source";s:16:"PRINTFUL_PRODUCT";s:7:"product";s:14:"5f5a8f842802b1";s:7:"variant";i:2135594502;}',
	}
}

$valid_pdt = false;
if (isset($_GET['tx'])) {
	$valid_pdt = validate_pdt($_GET['tx']);
}

//$pdt = new PayPalPDT($_POST);
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
					<?php
					if ($valid_pdt !== false) {
						$pdt = $valid_pdt;
						?>
                  <h1>Merch Ordered!</h1>

					<h4>Thank you for your order.</h4>
						
					<div>
						<p>You should receive an e-mail in a few minutes with your payment receipt.</p>
					</div>
					
					<div class="payment-information offset-top-20">
						<h5>Order Information</h5>
						<div class="payment-information-container">
							<ul>
								<li><strong>PayPal TX ID</strong>: <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=<?php echo $pdt->txn_id; ?>"><?php echo $pdt->txn_id; ?></a></li>
								<li><strong>Payment Amount</strong>: $<?php echo $pdt->mc_gross; ?></li>
								<li><strong>Product Type</strong>: <?php echo $pdt->option_selection1; ?></li>
								<li><strong>Product Name</strong>: <?php echo $pdt->option_selection2; ?></li>
								<li><strong>Product Variant</strong>: <?php echo $pdt->option_selection3; ?></li>
								<li><strong>Quantity</strong>: <?php echo $pdt->quantity; ?></li>
							</ul>
						</div>
						<div></div>
						<h5 class="offset-top-10">Shipping Address</h5>
						<div class="payment-information-container">
							<p><strong><?php echo $pdt->address_name; ?></strong></p>
							<p><?php echo $pdt->address_street; ?></p>
							<p><?php echo $pdt->address_city; ?>, <?php echo $pdt->address_state; ?> <?php echo $pdt->address_zip; ?></p>
							<p><?php echo $pdt->address_country_code; ?></p>
						</div>
					</div>
					
						<?php
					} else {
						?>
					<div class="alert alert-danger">This page has expired or we were unable to verify your payment.</div>
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