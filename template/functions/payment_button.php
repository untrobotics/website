<?php
use PayPal\PayPalAPI\BMCreateButtonReq;
use PayPal\PayPalAPI\BMCreateButtonRequestType;
use PayPal\PayPalAPI\InstallmentDetailsType;
use PayPal\PayPalAPI\OptionDetailsType;
use PayPal\PayPalAPI\OptionSelectionDetailsType;
use PayPal\Service\ButtonManagerService;

class PaymentButtonResponse {
	public $button = '';
	public $error = false;
}

class PaymentButton {
	private $item_name;
	private $amount;
	private $currency = 'USD';
	private $custom = '';
	private $opt_names = array();
	private $opt_vals = array();
	private $quantity = 1;
	private $no_shipping = NULL;
	private $complete_return_uri = PAYPAL_DEFAULT_RETURN_URL;
	private $cancel_return_uri = NULL;
	private $text;
	
	public function __construct($item_name, $amount, $text = "Buy Now", $cbt = null) {
		$this->item_name = $item_name;
		$this->amount = $amount;
		$this->text = $text;
	}
	
	function set_currency($currency) {
		$this->currency = $currency;
	}
	function get_currency() {
		return $this->currency;
	}
	
	function set_custom($custom) {
		$this->custom = $custom;
	}
	function get_custom() {
		return $this->custom;
	}
	
	function set_opt_names($opt_names) {
		$this->opt_names = $opt_names;
	}
	function get_opt_names() {
		return $this->opt_names;
	}
	
	function set_opt_vals($opt_vals) {
		$this->opt_vals = $opt_vals;
	}
	function get_opt_vals() {
		return $this->opt_vals;
	}
	
	function add_opt($opt_name, $opt_val) {
		$this->opt_names[] = $opt_name;
		$this->opt_vals[] = $opt_val;
	}
	
	function set_quantity($quantity) {
		$this->quantity = $quantity;
	}
	function get_quantity() {
		return $this->quantity;
	}
	
	function set_complete_return_uri($complete_return_uri) {
		$this->complete_return_uri = $complete_return_uri;
	}
	function get_complete_return_uri() {
		return $this->complete_return_uri;
	}
	
	function set_cancel_return_uri($cancel_return_uri) {
		$this->cancel_return_uri = $cancel_return_uri;
	}
	function get_cancel_return_uri() {
		return $this->cancel_return_uri;
	}
	
	function set_no_shipping($no_shipping) {
		$this->no_shipping = $no_shipping;
	}
	function get_no_shipping() {
		return $this->no_shipping;
	}
	
	function get_button() {
		global $untrobotics;
		
		if (is_null($this->cancel_return_uri)) {
			$this->cancel_return_uri = $_SERVER['REQUEST_URI'];
		}

		$response = new PaymentButtonResponse();

		if ($untrobotics->get_sandbox()) {
			require_once(BASE . '/paypal/PP-BM-SDK/Sandbox.Configuration.php');
		} else {
			require_once(BASE . '/paypal/PP-BM-SDK/Configuration.php');
		}
		require_once(BASE . '/paypal/PP-BM-SDK/vendor/autoload.php');

		$buttonVar = array();
		$buttonVar[] = 'item_name=' . $this->item_name;
		$buttonVar[] = 'amount=' . $this->amount;
		$buttonVar[] = 'quantity=' . $this->quantity;

		foreach ($this->opt_names as $key => $val) {
			$buttonVar[] = 'on' . $key . '=' . $this->opt_names[$key];
			$buttonVar[] = 'os' . $key . '=' . $this->opt_vals[$key];
		}

		$buttonVar[] = 'return=' . WEBSITE_URL . '/' . $this->complete_return_uri;
		if (!empty($cbt)) {
			$buttonVar[] = 'cbt=' . $cbt;
		}
		$buttonVar[] = 'rm=2'; // The buyer's browser is redirected to the return URL by using the POST method, and all payment variables are included.
		$buttonVar[] = 'cancel_return='. WEBSITE_URL . '/' . $this->cancel_return_uri;

		if ($untrobotics->get_sandbox()) {
			$buttonVar[] = 'business=' . PAYPAL_SANDBOX_BUSINESS_ID;
		} else {
			$buttonVar[] = 'business=' . PAYPAL_BUSINESS_ID;
		}

		$buttonVar[] = 'notify_url=' . PAYPAL_IPN_URL;
		if ($this->no_shipping == true) {
			$buttonVar[] = 'no_shipping=1';
		}
		$buttonVar[] = 'currency_code=' . $this->currency;
		$buttonVar[] = 'image_url=' . PAYPAL_IMAGE_LOGO;
		$buttonVar[] = 'custom=' . $this->custom;

		//var_dump($buttonVar);

		$createButtonRequest = new BMCreateButtonRequestType();
		$createButtonRequest->ButtonCode = 'ENCRYPTED';
		$createButtonRequest->ButtonType = 'BUYNOW';
		$createButtonRequest->ButtonVar = $buttonVar;
		$createButtonReq = new BMCreateButtonReq();
		$createButtonReq->BMCreateButtonRequest = $createButtonRequest;

		$paypalService = new ButtonManagerService(Configuration::getAcctAndConfig());
		try {
			$createButtonResponse = $paypalService->BMCreateButton($createButtonReq);
		} catch (Exception $ex) {
			$response->error = "An error has occured, E#1";
			//require 'buttonmanager-sdk-php/samples/Error.php';
			//exit;
		}
		if (isset($createButtonResponse)) {
			if ($createButtonResponse->Errors === NULL) {
				$response->button = '<div class="paypal-button-container">' .
									'<div class="paypal-button-overlay">' .
										'<img src="/images/paypal-button.png">' .
										'<button type="submit" id="buy-product-now" class="btn btn-primary">' . $this->text . '</button></div>' . 
									'<div class="actual-paypal-button">' . $createButtonResponse->Website . '</div>' .
								 '</div>';
			} else {
				$response->error = "An error has occured, E#2";
			}
		} else {
			$response->error = "An error has occured, E#3";
		}
		//require_once('buttonmanager-sdk-php/samples/Response.php');

		return $response;
	}
}

function payment_button(
	$item_name,
	$amount,
	$currency = 'USD',
	$custom = '',
	$opt_names = array(),
	$opt_vals = array(),
	$quantity = 1,
	$complete_return_uri = PAYPAL_DEFAULT_RETURN_URL,
	$cancel_return_uri = NULL
) {
	if (is_null($cancel_return_uri)) {
		$cancel_return_uri = $_SERVER['REQUEST_URI'];
	}
	
	$return = array();
	$return['error'] = false;
	$return['btn'] = '';

	if ($untrobotics->get_sandbox()) {
		require_once(BASE . '/paypal/PP-BM-SDK/Sandbox.Configuration.php');
	} else {
		require_once(BASE . '/paypal/PP-BM-SDK/Configuration.php');
	}
	require_once(BASE . '/paypal/PP-BM-SDK/vendor/autoload.php');

	$buttonVar = array();
	$buttonVar[] = 'item_name=' . $item_name;
	$buttonVar[] = 'amount=' . $amount;
	$buttonVar[] = 'quantity=' . $quantity;

	foreach ($opt_names as $key => $val) {
		$buttonVar[] = 'on' . $key . '=' . $opt_names[$key];
		$buttonVar[] = 'os' . $key . '=' . $opt_vals[$key];
	}

	$buttonVar[] = 'return=' . WEBSITE_URL . '/' . $complete_return_uri;
	$buttonVar[] = 'rm=2';
	$buttonVar[] = 'cancel_return='. WEBSITE_URL . '/' . $cancel_return_uri;
	
	if ($untrobotics->get_sandbox()) {
		$buttonVar[] = 'business=' . PAYPAL_SANDBOX_BUSINESS_ID;
	} else {
		$buttonVar[] = 'business=' . PAYPAL_BUSINESS_ID;
	}
	
	$buttonVar[] = 'notify_url=' . PAYPAL_IPN_URL;
	//$buttonVar[] = 'no_shipping=1';
	$buttonVar[] = 'currency_code=' . $currency;
	$buttonVar[] = 'image_url=' . PAYPAL_IMAGE_LOGO;
	$buttonVar[] = 'custom=' . $custom;

	//var_dump($buttonVar);

	$createButtonRequest = new BMCreateButtonRequestType();
	$createButtonRequest->ButtonCode = 'ENCRYPTED';
	$createButtonRequest->ButtonType = 'BUYNOW';
	$createButtonRequest->ButtonVar = $buttonVar;
	$createButtonReq = new BMCreateButtonReq();
	$createButtonReq->BMCreateButtonRequest = $createButtonRequest;

	$paypalService = new ButtonManagerService(Configuration::getAcctAndConfig());
	try {
		$createButtonResponse = $paypalService->BMCreateButton($createButtonReq);
	} catch (Exception $ex) {
		$return['error'] = "An error has occured, E#1";
		//require 'buttonmanager-sdk-php/samples/Error.php';
		//exit;
	}
	if (isset($createButtonResponse)) {
		if ($createButtonResponse->Errors === NULL) {
			$return['btn'] = '<div class="paypal-button-container">' .
								'<div class="paypal-button-overlay"></div>' . 
								'<div class="actual-paypal-button">' . $createButtonResponse->Website . '</div>' .
							 '</div>';
		} else {
			$return['error'] = "An error has occured, E#2 (" . $createButtonResponse->Errors . ")";
		}
	} else {
		$return['error'] = "An error has occured, E#3";
	}
	//require_once('buttonmanager-sdk-php/samples/Response.php');
	
	return $return;
}
?>