<?php
use PayPal\PayPalAPI\BMCreateButtonReq;
use PayPal\PayPalAPI\BMCreateButtonRequestType;
use PayPal\PayPalAPI\InstallmentDetailsType;
use PayPal\PayPalAPI\OptionDetailsType;
use PayPal\PayPalAPI\OptionSelectionDetailsType;
use PayPal\Service\ButtonManagerService;
	
function payment_button($item_name, $amount, $custom = '', $opt_names = array(), $opt_vals = array(), $quantity = 1, $complete_return_uri = PAYPAL_DEFAULT_RETURN_URL, $cancel_return_uri = NULL) {
	if (is_null($cancel_return_uri)) {
		$cancel_return_uri = $_SERVER['REQUEST_URI'];
	}
	
	$return = array();
	$return['error'] = false;
	$return['btn'] = '';

	if (is_sandbox()) {
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
	
	if (is_sandbox()) {
		$buttonVar[] = 'business=' . PAYPAL_SANDBOX_BUSINESS_ID;
	} else {
		$buttonVar[] = 'business=' . PAYPAL_BUSINESS_ID;
	}
	
	$buttonVar[] = 'notify_url=' . PAYPAL_IPN_URL;
	$buttonVar[] = 'no_shipping=1';
	$buttonVar[] = 'currency_code=USD';
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
			$return['error'] = "An error has occured, E#2";
		}
	} else {
		$return['error'] = "An error has occured, E#3";
	}
	//require_once('buttonmanager-sdk-php/samples/Response.php');
	
	return $return;
}
?>