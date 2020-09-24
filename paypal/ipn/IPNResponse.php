<?php
class IPNResponse {
	public $mc_gross; //' => '10.00',
	public $protection_eligibility; //' => 'Eligible',
	public $payer_id; //' => 'omitted',
	public $payment_date; //' => '08:52:14 Sep 21, 2020 PDT',
	public $payment_status; //' => 'Completed',
	public $charset; //' => 'windows-1252',
	public $first_name; //' => 'First',
	public $last_name; //' => 'Last',
	public $payer_email; //' => 'test@test.com',
	public $mc_fee; //' => '0.59',
	public $notify_version; //' => '3.9',
	public $custom; //' => 'DUES_PAYMENT',
	public $payer_status; //' => 'unverified',
	public $business; //' => 'unt.robotics@unt.edu',
	public $verify_sign; //' => 'AilMZJB6JglG1pRUgZXQTyXJgHXYAXB2Zn-c18AOrbP0k80nrsdrLago',
	public $txn_id; //' => '22W9940398711784Y',
	public $payment_type; //' => 'instant',
	public $receiver_email; //' => 'unt.robotics@unt.edu',
	public $payment_fee; //' => '0.59',
	public $shipping_discount; //' => '0.00',
	public $insurance_amount; //' => '0.00',
	public $receiver_id; //' => 'CBNJL3LAB26TY',
	public $txn_type; //' => 'web_accept',
	public $item_name; //' => 'UNT Robotics Dues',
	public $discount; //' => '0.00',
	public $mc_currency; //' => 'USD',
	public $item_number; //' => '',
	public $residence_country; //' => 'US',
	public $receipt_id; //' => '4021-1359-2767-2338',
	public $shipping_method; //' => 'Default',
	public $transaction_subject; //' => '',
	public $payment_gross; //' => '10.00',
	public $ipn_track_id; //' => '30a0baf50f16'
	
	// shipping
	public $address_status; // confirmed?
	public $address_name;
	public $address_street;
	public $address_city;
	
	public $address_state; // USA/Canada only??
	public $address_zip; // USA/Canada only??
	
	public $address_country_code;
	public $contact_phone;

	public $quantity; //' => '1',
	
	public $options = array();
	
	public function __construct($paypal_ipn_response_request) {
		$this->mc_gross = $paypal_ipn_response_request['mc_gross'];
		$this->protection_eligibility = $paypal_ipn_response_request['protection_eligibility'];
		$this->payer_id = $paypal_ipn_response_request['payer_id'];
		$this->payment_date = $paypal_ipn_response_request['payment_date'];
		$this->payment_status = $paypal_ipn_response_request['payment_status'];
		$this->charset = $paypal_ipn_response_request['charset'];
		$this->first_name = $paypal_ipn_response_request['first_name'];
		$this->last_name = $paypal_ipn_response_request['last_name'];
		$this->payer_email = $paypal_ipn_response_request['payer_email'];
		$this->mc_fee = $paypal_ipn_response_request['mc_fee'];
		$this->notify_version = $paypal_ipn_response_request['notify_version'];
		$this->custom = $paypal_ipn_response_request['custom'];
		$this->payer_status = $paypal_ipn_response_request['payer_status'];
		$this->business = $paypal_ipn_response_request['business'];
		$this->verify_sign = $paypal_ipn_response_request['verify_sign'];
		$this->txn_id = $paypal_ipn_response_request['txn_id'];
		$this->payment_type = $paypal_ipn_response_request['payment_type'];
		$this->receiver_email = $paypal_ipn_response_request['receiver_email'];
		$this->payment_fee = $paypal_ipn_response_request['payment_fee'];
		$this->shipping_discount = $paypal_ipn_response_request['shipping_discount'];
		$this->insurance_amount = $paypal_ipn_response_request['insurance_amount'];
		$this->receiver_id = $paypal_ipn_response_request['receiver_id'];
		$this->txn_type = $paypal_ipn_response_request['txn_type'];
		$this->item_name = $paypal_ipn_response_request['item_name'];
		$this->discount = $paypal_ipn_response_request['discount'];
		$this->mc_currency = $paypal_ipn_response_request['mc_currency'];
		$this->item_number = $paypal_ipn_response_request['item_number'];
		$this->residence_country = $paypal_ipn_response_request['residence_country'];
		!isset($paypal_ipn_response_request['receipt_id']) ?: $this->receipt_id = $paypal_ipn_response_request['receipt_id'];
		$this->shipping_method = $paypal_ipn_response_request['shipping_method'];
		$this->transaction_subject = $paypal_ipn_response_request['transaction_subject'];
		$this->payment_gross = $paypal_ipn_response_request['payment_gross'];
		$this->ipn_track_id = $paypal_ipn_response_request['ipn_track_id'];
		
		if (isset($paypal_ipn_response_request['address_status'])) {
			$this->address_status = $paypal_ipn_response_request['address_status'];
		}
		if (isset($paypal_ipn_response_request['address_name'])) {
			$this->address_name = $paypal_ipn_response_request['address_name'];
		}
		if (isset($paypal_ipn_response_request['address_street'])) {
			$this->address_street = $paypal_ipn_response_request['address_street'];
		}
		if (isset($paypal_ipn_response_request['address_city'])) {
			$this->address_city = $paypal_ipn_response_request['address_city'];
		}
		if (isset($paypal_ipn_response_request['address_state'])) {
			$this->address_state = $paypal_ipn_response_request['address_state'];
		}
		if (isset($paypal_ipn_response_request['address_zip'])) {
			$this->address_zip = $paypal_ipn_response_request['address_zip'];
		}
		if (isset($paypal_ipn_response_request['address_country_code'])) {
			$this->address_country_code = $paypal_ipn_response_request['address_country_code'];
		}
		!isset($paypal_ipn_response_request['contact_phone']) ?: $this->contact_phone = $paypal_ipn_response_request['contact_phone'];
		
		$this->quantity = $paypal_ipn_response_request['quantity'];
		
		$i = 1;
		while (isset($paypal_ipn_response_request["option_name{$i}"])) {
			$this->options[] = array($paypal_ipn_response_request["option_name{$i}"], $paypal_ipn_response_request["option_selection{$i}"]);
			$i++;
		}
	}
	
	// returns 0 when valid
	public function validate_shipping_address($country_code = null) {
		if ($country_code != null) {
			if ($this->address_country_code != $country_code) {
				return 1;
			}
		}
		
		if (!isset($this->address_name)) {
			return 2;
		}
		if (!isset($this->address_street)) {
			return 3;
		}
		if (!isset($this->address_city)) {
			return 4;
		}
		if (!isset($this->address_state)) {
			return 5;
		}
		if (!isset($this->address_zip)) {
			return 6;
		}
		
		return 0; // successfully validated
	}
}