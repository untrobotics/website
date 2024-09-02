<?php
require_once(__DIR__ . '/../../template/config.php');
require_once(__DIR__ . '/../../template/constants.php');
require_once(__DIR__ . '/api-json-objects.php');

class PayPalCustomApi
{

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


    //private $scope;

    /**
     * @param $paypal_client_id
     * @param $paypal_client_secret
     */

    public function __construct($paypal_client_id = PAYPAL_SANDBOX_API_CLIENT_ID, $paypal_client_secret = PAYPAL_SANDBOX_API_SECRET_KEY_1)
    {
        // insert stuff needed here
        $this->client_id = $paypal_client_id;
        $this->client_secret = $paypal_client_secret;
    }

    /**
     * @param string $URI The API endpoint, including starting forward slash (e.g., /v2/checkout/orders)
     * @param string $method The HTTP method to use. Should be passable to the CURLOPT_CUSTOMREQUEST option
     * @param string|false $data Data to be sent with the request
     * @param array|null $headers Additional headers to send with the request. The access token is already provided, and content type is set to json
     * @return bool|string
     * @throws PayPalCustomApiException
     */
    public function send_request(string $URI, string $method = "GET", $data = false, array $headers = null)
    {
        // get the access token if it hasn't already been retrieved
        if(!isset($this->access_token)){
            try {
                $this->get_access_token();
            } catch (PayPalCustomApiException $e){
                error_log($e);
                return null;
            }

        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, self::get_api_url() . $URI);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTP_VERSION,CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_ENCODING,'');

        if(!isset($headers)){
            $headers = array();
        }

        if($method !== "GET"){
            if($data !== false){
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = "Content-Type: application/json";
            }
        }

        $headers[] = "Authorization: Bearer $this->access_token";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new PayPalCustomApiException("Encountered an error executing an API request for PayPal at".self::get_api_url() ."{$URI}: " . curl_error($ch));
        }
        if ($httpcode < 200 || $httpcode >= 300) {
            if($httpcode >= 400 && $httpcode <= 403){
                throw new PayPalCustomApiException("Received non-success response from the PayPal API at ".self::get_api_url() ."{$URI} for bearer token '{$this->access_token}': {$httpcode}");
            }
            throw new PayPalCustomApiException("Received non-success response from the PayPal API at ".self::get_api_url() ."{$URI}: {$httpcode}");
        }
        curl_close($ch);
        return $result;
    }

    /**
     * @param PayPalItem[] $items An array containing all items' info
     * @param string[] $shipping_info An associative array containing necessary shipping info. Expected keys are 'full_name' 'phone_country_code' 'phone_number' 'address_1' 'address_2' 'address_country_code' 'postal_code' 'admin_area_1' 'admin_area_2'
     * @return string|bool
     */
    public function create_order(array $items, string $total, string $subtotal, string $total_tax, array $shipping_info) {
        if(count($items)<1) return false;
        $currency_code = $items[0]->unit_amount->currency_code;
        $data = new PayPalOrder(
            [
                new PayPalPurchaseUnit(
                    new PayPalItemsTotal(
                        $currency_code,
                        $total,
                        new PayPalBreakdown(
                            new PayPalCurrencyField($currency_code, $subtotal),
                            new PayPalCurrencyField($currency_code, $total_tax)
                        )
                    ),
                    new PayPalShipping(
                        new PayPalName($shipping_info['full_name']),
                        new PayPalPhoneNumber($shipping_info['phone_country_code'],$shipping_info['phone_number']),
                        new PayPalAddress(
                            $shipping_info['address_1'],
                            $shipping_info['admin_area_1'],
                            $shipping_info['address_country_code'],
                            $shipping_info['postal_code'],
                            $shipping_info['address_2'],
                            $shipping_info['admin_area_2']
                        ),
                        'SHIPPING'
                    ),
                    $items
                )
            ],
            'CAPTURE'
        );

        return $this->send_request('/v2/checkout/orders', 'POST', $data);
    }

    /**
     * Sets the access token for the
     * @return void
     * @throws PayPalCustomApiException
     */
    public function get_access_token()
    {
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
        curl_setopt($ch, CURLOPT_ENCODING,'');
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
        $result = json_decode($result,true);
        $this->access_token = $result["access_token"];
    }

    /**
     * Gives the proper base PayPal URL for the environment
     * @return string Returns the API URL based on environment
     */
    private static function get_api_url(): string{
        return ENVIRONMENT == Environment::DEVELOPMENT ? self::SANDBOX_API_BASE_URL : self::PRODUCTION_API_BASE_URL;
    }
}

class PayPalCustomApiException extends Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
    }
}
