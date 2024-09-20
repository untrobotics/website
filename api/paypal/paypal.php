<?php
require_once(__DIR__ . '/../../template/config.php');
require_once(__DIR__ . '/../../template/constants.php');    // used to get the right API URL based on environment
require_once(__DIR__ . '/api-json-objects.php');

/**
 * Class to interact with the PayPal API
 */
class PayPalCustomApi
{

    // PayPal gives 2 hostnames for both API URLs: "api-m" and "api". I have not noticed a difference between them nor is this documented anywhere in the documentation

    /**
     * Base URL for sandbox (development) API calls. Does not include trailing slash
     */
    private const SANDBOX_API_BASE_URL = 'https://api.sandbox.paypal.com';
    /**
     * Base URL for production API calls. Does not include trailing slash
     */
    private const PRODUCTION_API_BASE_URL = 'https://api.paypal.com';

    /**
     * @var string The client ID
     */
    private $client_id;
    /**
     * @var string The client secret
     */
    private $client_secret;

    /**
     * @var string The bearer token
     */
    private $access_token;


    // This was supposed to store the scope of the OAuth token, but PayPal doesn't have an easy-to-access list of scopes, so it's the same everytime
    //private $scope;

    /**
     * Creates an object to interact with the PayPal API. Client secret and ID defaults are based on ENVIRONMENT
     * @param $paypal_client_id null|string The client ID. Defaults to the value stored in config.php
     * @param $paypal_client_secret null|string The client secret. Defaults to the value stored in config.php
     */
    public function __construct(?string $paypal_client_id = null, ?string $paypal_client_secret = null) {
        $this->client_id = isset($paypal_client_id) ? $paypal_client_id : ENVIRONMENT == Environment::DEVELOPMENT ? PAYPAL_SANDBOX_API_CLIENT_ID : PAYPAL_API_CLIENT_ID;
        $this->client_secret = isset($paypal_client_secret) ? $paypal_client_secret : ENVIRONMENT == Environment::DEVELOPMENT ? PAYPAL_SANDBOX_API_SECRET_KEY_1 : PAYPAL_API_SECRET_KEY_1;
    }

    /**
     * Creates an order and returns the JSON response
     * @param PayPalItem[] $items An array containing all items' info
     * @param string $total The total amount owed for the order ($subtotal + $total_tax)
     * @param string $subtotal The amount owed for the order, excluding tax and discounts
     * @param string $total_tax The total amount of tax owed for the order
     * @param bool|string[] $shipping_info Set to true if shipping info will be given during the payment process. Set to false if there is no shipping. If information is already stored, set to
     *  an associative array containing necessary shipping info. Expected keys are 'full_name' 'phone_country_code' 'phone_number' 'address_1' 'address_2' 'address_country_code' 'postal_code' 'admin_area_1' 'admin_area_2'
     * @param string|null $discount The discount taken from the order, in currency amounts (i.e., not percentage of the price)
     * @param string $return_url URL to return the user upon order approval
     * @param string $cancel_url URL to return the user upon order cancellation
     * @return string|null|bool JSON-encoded string with order creation details or null if an access token couldn't be retrieved. Returns false if no items are passed
     * @throws PayPalCustomApiException Error if non-success code from PayPal API
     */
    public function create_order(array $items, string $total, string $subtotal, string $total_tax, $shipping_info = false, ?string $discount = null, string $return_url = 'https://untrobotics.com/', string $cancel_url = 'https://untrobotics.com/'): ?string {
        // don't create an order if no items are given
        if (count($items) < 1) return false;
        // assume all items have the same currency code
        $currency_code = $items[0]->unit_amount->currency_code;

        // use the provided shipping info if given, otherwise set shipping to be obtained during checkout process or not at all based on the $shipping_info as a boolean
        if (is_array($shipping_info)) {
            $shipping_info_obj = new PayPalShipping(
                new PayPalName($shipping_info['full_name']),
                new PayPalPhoneNumber($shipping_info['phone_country_code'], $shipping_info['phone_number']),
                new PayPalAddress(
                    $shipping_info['address_1'],
                    $shipping_info['admin_area_1'],
                    $shipping_info['address_country_code'],
                    $shipping_info['postal_code'],
                    $shipping_info['address_2'],
                    $shipping_info['admin_area_2']
                ),
                'SHIPPING'
            );
            $shipping_pref = 'SET_PROVIDED_ADDRESS';
        } else {
            $shipping_info_obj = null;
            if ($shipping_info) {
                $shipping_pref = 'GET_FROM_FILE';
            } else {
                $shipping_pref = 'NO_SHIPPING';
            }
        }

        // request body
        $data = new PayPalOrder(
            [
                new PayPalPurchaseUnit(
                    new PayPalItemsTotal(
                        $currency_code,
                        $total,
                        new PayPalBreakdown(
                            new PayPalCurrencyField($currency_code, $subtotal),
                            new PayPalCurrencyField($currency_code, $total_tax),
                            isset($discount) ? new PayPalCurrencyField($currency_code, $discount) : null
                        )
                    ),
                    $items,
                    $shipping_info_obj
                )
            ],
            'CAPTURE',
            new PayPalPaymentSource(
                new PayPalPaymentSourcePayPal(
                    new PayPalExperienceContext(
                        null, $shipping_pref, 'NO_PREFERENCE',
                        'PAY_NOW', 'UNRESTRICTED', 'en-US',
                        $return_url, $cancel_url
                    )
                ))
        );
        // send request and return the response
        return $this->send_request('/v2/checkout/orders', 'POST', $data);
    }

    /**
     * Gets order details
     * @param string $order_id ID of the order to retrieve
     * @return string|null JSON-encoded string with order details or null if an access token couldn't be retrieved
     * @throws PayPalCustomApiException Throws an error on cURL error or non-success response is received from PayPal
     */
    public function get_order_info(string $order_id): ?string {
        $uri = '/v2/checkout/orders/$1';
        $headers = ['Content-Type: application/json'];
        return $this->send_request($uri, 'GET', false, $headers, $order_id);
    }

    /**
     * Captures payment for an order. Requires payment to have already been approved by the payer.
     * If payment is not captured, the customer will not be charged anything, and the order will be deleted from PayPal and not show up in transaction history
     * @param string $order_id The ID of the order to capture payment from
     * @param bool $return_minimal Set to true to return minimum info (order id, order status, HATEOAS links)
     * @return string|null JSON-encoded string with order details or null if an access token couldn't be retrieved
     * @throws PayPalCustomApiException Throws an error on cURL error or non-success response is received from PayPal
     */
    public function capture_payment(string $order_id, bool $return_minimal = true): ?string {
        echo $order_id;
        $headers = array();
        if ($return_minimal) {
            $headers[] = 'Prefer: return=minimal';
        } else {
            $headers[] = 'Prefer: return=representation';
        }
        $data = new PayPalPaymentSource(
            new PayPalPaymentSourcePayPal(new PayPalExperienceContext())
        );

        return $this->send_request('/v2/checkout/orders/$1/capture', 'POST', $data, $headers, $order_id);
    }

    /**
     * Gets payment details
     * @param string $payment_id The PayPal-generated ID for the captured payment.
     * @return string|null JSON-encoded string with order details or null if an access token couldn't be retrieved
     * @throws PayPalCustomApiException Throws an error on cURL error or non-success response is received from PayPal
     */
    public function get_payment_details(string $payment_id): ?string{
        $headers = ['Content-Type: application/json'];
        return $this->send_request('/v2/payments/captures/$1', 'GET', false, $headers, $payment_id);
    }

    /**
     * Sends a request to the PayPal API. Gets a new access token if one isn't already set
     * @param string $URI The API endpoint, including starting forward slash (e.g., /v2/checkout/orders)
     * @param string $method The HTTP method to use. Should be passable to the CURLOPT_CUSTOMREQUEST option
     * @param string|false $data Data to be sent with the request
     * @param array|null $headers Additional headers to send with the request. The access token is already provided, and content type is set to json
     * @param string ...$args Args to insert into $URI (replaces $1, $2, $3... with args[0], args[1], args[2]...)
     * @return string|null JSON-encoded string from the response body or null if an access token couldn't be retrieved
     * @throws PayPalCustomApiException Throws an error if cURL fails or a non-success response is returned
     */
    public function send_request(string $URI, string $method = "GET", $data = false, array $headers = null, ...$args): ?string {
        // get the access token if it hasn't already been retrieved
        if (!isset($this->access_token)) {
            try {
                $this->get_access_token();
            } catch (PayPalCustomApiException $e) {
                error_log($e);
                return null;
            }

        }
        $ch = curl_init();
        $full_url = self::get_api_url() . insert_args($URI, ...$args);
        curl_setopt($ch, CURLOPT_URL, $full_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_ENCODING, '');

        if (!isset($headers)) {
            $headers = array();
        }

        if ($method !== "GET") {
            if ($data !== false) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                // may need to move this if using some other endpoints, but most of them want json
                $headers[] = "Content-Type: application/json";
            }
        }

        $headers[] = "Authorization: Bearer $this->access_token";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new PayPalCustomApiException("Encountered an error executing an API request for PayPal at {$full_url}:" . curl_error($ch));
        }
        if ($httpcode < 200 || $httpcode >= 300) {
            if ($httpcode >= 400 && $httpcode <= 403) {
                throw new PayPalCustomApiException("Received non-success response from the PayPal API at {$full_url} for bearer token '{$this->access_token}': {$httpcode}: result: {$result}");
            }
            throw new PayPalCustomApiException("Received non-success response from the PayPal API at {$full_url}: {$httpcode}: response: {$result}");
        }
        curl_close($ch);
        return $result;
    }

    /**
     * Gets an OAuth access token for the object
     * @throws PayPalCustomApiException Error if cURL fails or if PayPal doesn't return the expected success code (200)
     */
    public function get_access_token(): void {
        /**
         * The cURL command looks like:
         *      curl -v -X POST "https://api-m.sandbox.paypal.com/v1/oauth2/token"\
         *      -u "CLIENT_ID:CLIENT_SECRET"\
         *      -H "Content-Type: application/x-www-form-urlencoded"\
         *      -d "grant_type=client_credentials"
         * per https://developer.paypal.com/api/rest/authentication/
         */

        $ch = curl_init();

        $headers = array();
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';

        curl_setopt($ch, CURLOPT_URL, self::get_api_url() . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERPWD, $this->client_id . ':' . $this->client_secret);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new PayPalCustomApiException('Encountered an error while trying to retrieve access token: ' . curl_error($ch));
        }
        if ($httpcode != 200) {
            throw new PayPalCustomApiException("Received non-success response while trying to retrieve access token: {$httpcode}");
        }

        curl_close($ch);
        /**
         * $result should look like this:
         *  {
         *      "scope"         : "https://uri.paypal.com/.... https://uri.paypal.com/.... ....",
         *      "access_token"  : "token",
         *      "token_type"    : "Bearer",
         *      "app_id"        : "APP-ID",
         *      "expires_in"    :  32400,
         *      "nonce"         : "nonce"
         *  }
         */
        $result = json_decode($result, true);
        $this->access_token = $result["access_token"];
    }

    /**
     * Gives the proper base PayPal URL for the environment
     * @return string Returns the API URL based on ENVIRONMENT
     */
    private static function get_api_url(): string {
        return ENVIRONMENT == Environment::DEVELOPMENT ? self::SANDBOX_API_BASE_URL : self::PRODUCTION_API_BASE_URL;
    }

    /*
        public function confirm_order(string $id, bool $minimal_return = true)
        {
            $headers = array();
            $data = null;
    //        $this->send_request('/v2/checkout/orders/$1/confirm-payment-source', 'POST', $headers,);
        }*/


}

/**
 * Generic exception class for PayPal API errors.
 */
class PayPalCustomApiException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
    }
}

//todo: delete below when #158 is approved
function insert_args(string $endpoint, ...$args): string {
    if (count($args) === 0) {
        return $endpoint;
    }
    $search = array();
    for ($i = 1; $i <= count($args); $i++) {
        $search[] = '$' . $i;
    }
    return str_replace($search, $args, $endpoint);
}