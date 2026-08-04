<?php
require('../../template/top.php');
head('Merch Ordered', true);

$log = var_export($_REQUEST, true);
$log .= var_export($userinfo, true);
error_log($log, 3, BASE . '/paypal/logs/pdt-merch.log');

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

// Stripe Express Checkout Element (Apple Pay / Google Pay) returns here with
// ?payment_intent=...&redirect_status=succeeded. Fulfilment is handled by the
// webhook; this page just confirms the payment to the buyer.
$stripe_pi = null;
$stripe_status = null;
if (isset($_GET['payment_intent'])) {
	require_once(BASE . '/api/stripe/vendor/autoload.php');
	\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
	try {
		$stripe_pi = \Stripe\PaymentIntent::retrieve($_GET['payment_intent']);
		$stripe_status = $stripe_pi->status;
	} catch (Exception $e) {
		$stripe_pi = null;
	}
}

//$pdt = new PayPalPDT($_POST);
?>
<?php
// Printful/Stripe often stores the item as "Product - Variant" where the variant
// label repeats the product name ("Bomber Jacket (Gear) - Bomber Jacket (Gear) / XL").
// Collapse that so the buyer sees one clean line.
function oc_clean_item($s) {
    $s = trim((string) $s);
    if (strpos($s, ' - ') !== false) {
        list($a, $b) = array_map('trim', explode(' - ', $s, 2));
        if ($a !== '' && stripos($b, $a) === 0) {
            return $b;
        }
    }
    return $s;
}

if ($stripe_pi && $stripe_status === 'succeeded') {
    $s_item = oc_clean_item(isset($stripe_pi->metadata['item_name']) ? $stripe_pi->metadata['item_name'] : 'Your order');
    $s_amount = number_format($stripe_pi->amount / 100, 2);
    $s_ship = $stripe_pi->shipping;
    $addr = '';
    if ($s_ship && isset($s_ship->address)) {
        $a = $s_ship->address;
        $addr = '<strong>' . htmlspecialchars($s_ship->name) . '</strong><br>'
            . htmlspecialchars($a->line1) . ($a->line2 ? ', ' . htmlspecialchars($a->line2) : '') . '<br>'
            . htmlspecialchars($a->city) . ', ' . htmlspecialchars($a->state) . ' ' . htmlspecialchars($a->postal_code) . '<br>'
            . htmlspecialchars($a->country);
    }
    result_card([
        'status'     => 'success',
        'title'      => 'Merch Ordered!',
        'subtitle'   => 'Thank you for your order.',
        'lead'       => 'Your payment was successful. You&rsquo;ll receive an e-mail receipt shortly, and a tracking number once your order ships.',
        'rows_label' => 'Order summary',
        'rows'       => [['Item', htmlspecialchars($s_item)]],
        'total'      => ['Amount paid', '$' . htmlspecialchars($s_amount)],
        'address'    => $addr,
        'button'     => ['href' => '/merch', 'label' => 'Continue shopping'],
        'note'       => 'Questions about your order? <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>',
    ]);
} elseif ($stripe_pi) {
    result_card([
        'status'   => 'wait',
        'title'    => 'Payment Processing',
        'subtitle' => 'Your payment is being processed.',
        'lead'     => 'This can take a moment. You&rsquo;ll receive an e-mail receipt once it completes &mdash; no need to pay again. If you don&rsquo;t hear from us, contact <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>.',
        'button'   => ['href' => '/merch', 'label' => 'Back to merch'],
    ]);
} elseif ($valid_pdt !== false) {
    $pdt = $valid_pdt;
    $addr = '<strong>' . htmlspecialchars($pdt->address_name) . '</strong><br>'
        . htmlspecialchars($pdt->address_street) . '<br>'
        . htmlspecialchars($pdt->address_city) . ', ' . htmlspecialchars($pdt->address_state) . ' ' . htmlspecialchars($pdt->address_zip) . '<br>'
        . htmlspecialchars($pdt->address_country_code);
    $product = htmlspecialchars($pdt->option_selection2) . ($pdt->option_selection3 ? ' &middot; ' . htmlspecialchars($pdt->option_selection3) : '');
    $txlink = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_view-a-trans&id=' . htmlspecialchars($pdt->txn_id) . '">' . htmlspecialchars($pdt->txn_id) . '</a>';
    result_card([
        'status'     => 'success',
        'title'      => 'Merch Ordered!',
        'subtitle'   => 'Thank you for your order.',
        'lead'       => 'You should receive an e-mail in a few minutes with your payment receipt.',
        'rows_label' => 'Order summary',
        'rows'       => [
            ['Product', $product],
            ['Quantity', htmlspecialchars($pdt->quantity)],
            ['Reference', $txlink],
        ],
        'total'      => ['Amount paid', '$' . htmlspecialchars($pdt->mc_gross)],
        'address'    => $addr,
        'button'     => ['href' => '/merch', 'label' => 'Continue shopping'],
        'note'       => 'Questions about your order? <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>',
    ]);
} else {
    result_card([
        'status'   => 'error',
        'title'    => "Couldn't Confirm",
        'subtitle' => "We couldn't verify this payment.",
        'lead'     => 'This confirmation link may have expired. If you completed a payment, don&rsquo;t worry &mdash; your order is still processing and you&rsquo;ll get an e-mail receipt. Reach us at <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a> if anything looks wrong.',
        'button'   => ['href' => '/merch', 'label' => 'Back to merch'],
    ]);
}

footer();
