<?php
require_once(__DIR__ . '/../../template/config.php');
require_once(__DIR__ . '/../../template/constants.php');

class PayPalCustomApi
{

    /**
     * Base URL for sandbox (development) API calls. Does not include trailing slash
     */
    private const SANDBOX_API_BASE_URL = 'https://api-m.sandbox.paypal.com';
    /**
     * Base URL for production API calls. Does not include trailing slash
     */
    private const PRODUCTION_API_BASE_URL = 'https://api-m.paypal.com';


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
     * @param string $method
     * @return void
     */
    public function send_request(string $method)
    {
        if(!isset($this->access_token)){
            try {
                $this->get_access_token();
            } catch (PayPalCustomApiException $e){
                error_log($e);
                return null;
            }
        }


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

        curl_setopt($ch, CURLOPT_URL, self::get_api_url() . 'v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERPWD, $this->client_id . ':' . $this->client_secret);

        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        var_dump($result);
        if (curl_errno($ch)) {
            throw new PayPalCustomApiException('Encountered an error while trying to retrieve access token: ' . curl_error($ch));
        }
        if ($httpcode != 200) {
            throw new PayPalCustomApiException("Received non-success response while trying to retrieve access token: {$httpcode}");
        }

        curl_close($ch);
        /**
         * Result should look like this:
         *  {
         *      "scope"         : "https://uri.paypal.com/.... https://uri.paypal.com/.... ....",
         *      "access_token"  : "token",
         *      "token_type"    : "Bearer",
         *      "app_id"        : "APP-ID",
         *      "expires_in"    :  32400,
         *      "nonce"         : "nonce"
         *  }
         */
        $result = json_decode($result);
        $this->access_token = $result["access_token"];
    }

    /**
     * Gives the proper base PayPal URL for the environment
     * @return string Returns the API URL based on environment
     */
    private static function get_api_url(): string{
        /*if(ENVIRONMENT == Environment::DEVELOPMENT){

        }*/
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