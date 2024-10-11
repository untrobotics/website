<?php
require_once(__DIR__ . "/paypal.php");

/**
 * Class to interact with webhook event request body.
 */
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

    /**
     * @var array|false HTTP headers received. Stores the data returned by {@see getallheaders()}
     */
    public $headers;


    /**
     * @var bool True if the webhook event is from sandbox (development). False otherwise.
     * See {@see PaypalWebhookEvent::is_sandbox()} for how this is determined
     */
    private $is_sandbox;

    /**
     * @param string $raw The raw data sent to the webhook. The data can be obtained from php://input by using {@see file_get_contents()}
     * @param string $paypal_client_id The client ID. Defaults to the constant defined in config.php
     * @param string $paypal_client_secret The secret key. Defaults to the constant defined in config.php
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
        if (preg_match('/\/v[12]\/notifications\/certs\/(?:CERT)?[a-z0-9\-]+/', parse_url($this->headers['PAYPAL-CERT-URL'], PHP_URL_PATH)) !== 1) {
            throw new PayPalCustomApiException("Received an unexpected path for PayPal Cert URL. Full URL: {$this->headers['PAYPAL-CERT-URL']}");
        }

        $public_key = openssl_pkey_get_public(file_get_contents(__DIR__ . '/../../paypal/cert/paypal-cert'));

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

    /**
     * Whether the webhook event is from the sandbox API or production. This is determined by the URL that created the event, which will have 'sandbox' in the subdomain.
     * If this method has already been called once, then the previous value will be returned.
     * @return bool True if the webhook event is from sandbox (development). False if it's from production.
     */
    public function is_sandbox(): bool {
        if (isset($this->is_sandbox)) {
            return $this->is_sandbox;
        }
        foreach ($this->payload['links'] as $link) {
            if ($link['rel'] === 'self') {  // self refers to the API endpoint that caused the event
                // PayPal has an 'api-m' and a 'api' subdomain, although it's not stated what the difference is
                $this->is_sandbox = preg_match('/^https?:\/\/api(?:-m)?\.sandbox\.paypal\.com/i', $link['href']);
                return $this->is_sandbox;
            }
        }
        $this->is_sandbox = false;
        return false;
    }

    /**
     * Determines if the webhook event was already handled
     * @return bool True if a previous webhook transmission received a SUCCESS response. False otherwise
     */
    public function already_handled(): bool{
        if(!isset($this->payload['transmissions'])){
            return false;
        }
        foreach ($this->payload['transmissions'] as $transmission) {
            if($transmission['status']==='SUCCESS'){
                return true;
            }
        }
        return false;
    }
}

/**
 * Generic exception class for webhook handler errors.
 * Replaces the IPNHandlerException that was used with the PayPal ButtonManager SDK.
 */
class WebhookEventHandlerException extends Exception
{
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
    }
}