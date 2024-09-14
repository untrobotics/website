<?php
require_once(__DIR__ . "/paypal.php");

class PaypalWebhookEvent extends PayPalCustomApi
{

    /**
     * The URI to externally verify webhook events via PayPal's API
     */
    private const VERIFY_URI = '/v1/notifications/verify-webhook-signature';

    /**
     * @var string The raw data sent to the webhook
     */
    public $raw;
    /**
     * @var array JSON decoded associative array of the data received. The same data as {@see $raw}, but converted to an array
     */

    public $payload;
    public $headers;


    private $is_sandbox;

    /**
     * @param string $raw The raw data sent to the webhook. The data can be obtained from php://input by using {@see file_get_contents()}
     * @param string $paypal_client_id The client ID
     * @param string $paypal_client_secret The secret key
     */
    public function __construct(string $raw, $paypal_client_id = PAYPAL_SANDBOX_API_CLIENT_ID, $paypal_client_secret = PAYPAL_SANDBOX_API_SECRET_KEY_1) {
        parent::__construct($paypal_client_id, $paypal_client_secret);
        $this->raw = $raw;
        $this->payload = json_decode($raw, true);
        $this->headers = getallheaders();
    }

    /**
     * Uses PayPal's certificate
     * @return bool Whether the message is valid or not
     * @throws PayPalCustomApiException If the request doesn't have the proper information to verify payload request
     */
    public function verify_internal(): bool {

        // verify a cert URL was sent
        if (!isset($this->headers['PAYPAL-CERT-URL'])) {
            throw new PayPalCustomApiException('Tried to verify a PayPal Webhook event via CRC but no cert-url was passed to the endpoint.');
        }
        // verify that the url is from one of PayPal's api domains
        if (preg_match('/api\.(?:sandbox)?\.paypal\.com/i', parse_url($this->headers['PAYPAL-CERT-URL'], PHP_URL_HOST)) !== 1) {
            throw new PayPalCustomApiException("Received an unexpected hostname for PayPal Cert URL. Full URL: {$this->headers['PAYPAL-CERT-URL']}");
        }
        // verify that the url is in the expected path and filename format
        if (preg_match('/\/v[12]\/notifications\/certs\/[a-z0-9\-]+/', parse_url($this->headers['PAYPAL-CERT-URL'], PHP_URL_PATH)) !== 1) {
            throw new PayPalCustomApiException("Received an unexpected path for PayPal Cert URL. Full URL: {$this->headers['PAYPAL-CERT-URL']}");
        }

        //todo: run the cert through the cache system?
        $public_key = openssl_pkey_get_public(file_get_contents($this->headers['PAYPAL-CERT-URL']));

        return openssl_verify(
            "{$this->headers['PAYPAL-TRANSMISSION-ID']}|{$this->headers['PAYPAL-TRANSMISSION-TIME']}|" . self::get_webhook_id() . '|' . crc32($this->raw),
            base64_decode($this->headers['PAYPAL-TRANSMISSION-SIG']),
            $public_key,
            'sha256WithRSAEncryption'
        );
    }

    private static function get_webhook_id(): string {
        return ENVIRONMENT == Environment::DEVELOPMENT ? PAYPAL_SANDBOX_WEBHOOK_ID : PAYPAL_WEBHOOK_ID;
    }

    /**
     * Uses the PayPal API to verify webhook events
     * @return bool Whether the message is valid or not
     * @throws PayPalCustomApiException
     */
    public function verify_external() {
        $data = new stdClass();
        $data->transmission_id = $this->headers['PAYPAL-TRANSMISSION-ID'];
        $data->transmission_time = $this->headers['PAYPAL-TRANSMISSION-TIME'];
        $data->cert_url = $this->headers['PAYPAL-CERT-URL'];
        $data->auth_algo = $this->headers['PAYPAL-AUTH-ALGO'];
        $data->transmission_sig = $this->headers['PAYPAL-TRANSMISSION-SIG'];
        $data->webhook_id = self::get_webhook_id();
        $data->webhook_event = $this->payload;
        $result = json_decode($this->send_request(self::VERIFY_URI, 'POST', $data), true);

        return $result["verification_status"] === "SUCCESS";
    }

    public function is_sandbox(): bool{
        if(isset($this->is_sandbox)) {
            return $this->is_sandbox;
        }
        foreach($this->payload['links'] as $link){
            if($link['rel']==='self'){  // self refers to the API endpoint that caused the event
                $this->is_sandbox =preg_match('/^https?:\/\/api(?:-m)?\.sandbox\.paypal\.com/i',$link['href']);
                return $this->is_sandbox;
            }
        }
        $this->is_sandbox = false;
        return false;
    }
}

class WebhookEventHandlerException extends Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
    }
}