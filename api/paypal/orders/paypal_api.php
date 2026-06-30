<?php
/**
 * Minimal PayPal Orders v2 client (raw cURL — no composer dependency).
 *
 * Replaces the dead Button Manager SDK. Used by api/paypal/orders/create.php and
 * api/paypal/orders/capture.php to drive the modern JS SDK Smart Buttons flow:
 *   1. get an OAuth2 access token (client_credentials)
 *   2. create a v2 Order (intent=CAPTURE)
 *   3. capture a v2 Order
 *
 * The base URL (api-m.sandbox.paypal.com vs api-m.paypal.com) and the client
 * credentials are chosen by the $sandbox flag, which the callers derive from
 * $untrobotics->get_sandbox() — exactly like the old PaymentButton code picked
 * PAYPAL_SANDBOX_BUSINESS_ID vs PAYPAL_BUSINESS_ID.
 */

class PayPalAPIException extends Exception {}

class PayPalOrdersAPI {
	private $sandbox;
	private $client_id;
	private $client_secret;
	private $base_url;

	public function __construct($sandbox) {
		$this->sandbox = (bool) $sandbox;
		if ($this->sandbox) {
			$this->base_url = 'https://api-m.sandbox.paypal.com';
			$this->client_id = PAYPAL_SANDBOX_CLIENT_ID;
			$this->client_secret = PAYPAL_SANDBOX_CLIENT_SECRET;
		} else {
			$this->base_url = 'https://api-m.paypal.com';
			$this->client_id = PAYPAL_CLIENT_ID;
			$this->client_secret = PAYPAL_CLIENT_SECRET;
		}

		if (empty($this->client_id) || empty($this->client_secret)) {
			throw new PayPalAPIException('PayPal REST credentials are not configured (' . ($this->sandbox ? 'sandbox' : 'live') . ').');
		}
	}

	public function get_sandbox() {
		return $this->sandbox;
	}

	/** OAuth2 client_credentials grant -> access token string. */
	private function get_access_token() {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->base_url . '/v1/oauth2/token');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERPWD, $this->client_id . ':' . $this->client_secret);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

		$result = curl_exec($ch);
		$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($result === false) {
			$err = curl_error($ch);
			curl_close($ch);
			throw new PayPalAPIException("PayPal token request failed: {$err}");
		}
		curl_close($ch);

		$json = json_decode($result, true);
		if ($http < 200 || $http >= 300 || !isset($json['access_token'])) {
			throw new PayPalAPIException("PayPal token request returned {$http}: {$result}");
		}
		return $json['access_token'];
	}

	/** Generic authenticated JSON request against the REST API. */
	private function request($method, $path, $body = null) {
		$token = $this->get_access_token();

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->base_url . $path);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$headers = array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $token,
		);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		if ($body !== null) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
		}

		$result = curl_exec($ch);
		$http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($result === false) {
			$err = curl_error($ch);
			curl_close($ch);
			throw new PayPalAPIException("PayPal {$method} {$path} failed: {$err}");
		}
		curl_close($ch);

		$json = json_decode($result, true);
		if ($http < 200 || $http >= 300) {
			throw new PayPalAPIException("PayPal {$method} {$path} returned {$http}: {$result}");
		}
		return $json;
	}

	/**
	 * Create a v2 Order. $order is the full purchase payload (intent,
	 * purchase_units, application_context). Returns the decoded response
	 * (->['id'] is the order id handed back to the JS SDK).
	 */
	public function create_order($order) {
		return $this->request('POST', '/v2/checkout/orders', $order);
	}

	/** Capture a previously-created/approved order. Returns the decoded response. */
	public function capture_order($order_id) {
		return $this->request('POST', '/v2/checkout/orders/' . rawurlencode($order_id) . '/capture', new stdClass());
	}
}
