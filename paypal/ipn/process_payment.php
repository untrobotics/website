<?php
/**
 * Shared payment-completion adapter.
 *
 * This is the single place that turns a *normalized* payment record into the
 * same side effects the PayPal IPN produces (dues_payments rows, Printful
 * orders, receipts, Discord messages). It is gateway-agnostic: PayPal's IPN
 * keeps using paypal/ipn.php directly, while Stripe (api/stripe/webhook.php)
 * builds an IPNResponse-shaped record with build_ipn_response() and calls
 * process_normalized_payment().
 *
 * Good standing remains a pure function of dues_payments rows, so writing the
 * same rows here automatically grants the Discord role later via auth/discord.php
 * — no Discord logic lives in this file.
 *
 * Requirements before calling process_normalized_payment():
 *   - template/top.php has been required (BASE, $db, email(), Semester, etc.)
 *   - api/discord/bots/admin.php has been required (AdminBot)
 * Both handlers run with the working directory set to paypal/ (the IPN's CWD),
 * so callers outside paypal/ should chdir(BASE . '/paypal') first to keep the
 * handlers' relative paths (logs/, ../images/) working.
 */

require_once(__DIR__ . '/IPNResponse.php');

// --- Shared symbols, guarded so this file can be loaded alongside paypal/ipn.php

if (!class_exists('IPNHandlerException')) {
    class IPNHandlerException extends Exception {
        public function __construct($message, $code = 0, Exception $previous = null) {
            parent::__construct($message, $code, $previous);
        }
        public function __toString() {
            return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
        }
    }
}

if (!class_exists('Source')) {
    class Source {
        const PRINTFUL = 'PRINTFUL_PRODUCT';
        const DUES = 'DUES_PAYMENT';
        const DONATION = 'DONATION';
        const KIT = 'KIT_PREORDER';
    }
}

// payment_log() is defined in paypal/ipn.php for the legacy IPN path. Define an
// identical one here for non-IPN callers (writes to paypal/logs/ipn.log relative
// to the CWD, which callers set to paypal/).
if (!function_exists('payment_log')) {
    function payment_log($message) {
        file_put_contents('logs/ipn.log', '[' . date('c', time()) . '] ' . $message . PHP_EOL, FILE_APPEND);
    }
}

// Idempotency ledger — the SAME handled_ipns table the PayPal IPN uses.
if (!function_exists('handled_tx')) {
    function handled_tx($tx_id, $source) {
        global $db;
        if (!$db->query('INSERT INTO handled_ipns (txid, handled_source) VALUES ("' . $db->real_escape_string($tx_id) . '", "' . $db->real_escape_string($source) . '")')) {
            throw new Exception("Unable to mark the transaction as handled in the database");
        }
    }
}

if (!function_exists('already_handled')) {
    function already_handled($tx_id) {
        global $db;
        $q = $db->query('SELECT handled_timestamp, handled_source FROM handled_ipns WHERE txid = "' . $db->real_escape_string($tx_id) . '"');
        if ($q) {
            if ($q->num_rows > 0) {
                return $q->fetch_row();
            }
        } else {
            throw new Exception("Failed to retrieve handled transaction IDs from the database");
        }
        return false;
    }
}

// Release a claim so a retry can re-run the fulfilment. Used only when the
// handler failed before completing, so the up-front claim must not permanently
// block the retry that would actually fulfil the (already-charged) order.
if (!function_exists('release_tx')) {
    function release_tx($tx_id) {
        global $db;
        $db->query('DELETE FROM handled_ipns WHERE txid = "' . $db->real_escape_string($tx_id) . '"');
    }
}

/**
 * Minimal stand-in for the PayPalIPN object the handlers expect. The handlers
 * only ever call getSandbox() on it (to decide whether to actually confirm a
 * Printful order). For Stripe we map sandbox === true to "test mode" so test-key
 * payments never confirm a real fulfillment.
 */
if (!class_exists('PaymentGatewayContext')) {
    class PaymentGatewayContext {
        private $sandbox;
        private $name;
        public function __construct($sandbox, $name = 'PayPal') {
            $this->sandbox = (bool) $sandbox;
            $this->name = $name;
        }
        public function getSandbox() {
            return $this->sandbox;
        }
        public function useSandbox() {
            $this->sandbox = true;
        }
        public function get_name() {
            return $this->name;
        }
        // Gateway-aware admin alert: prefixes the message with "IPN/<provider>"
        // (e.g. "(IPN/Stripe) ...", "(IPN/PayPal) ...") so alerts show which
        // gateway they came from.
        public function alert($message) {
            if (class_exists('AdminBot')) {
                AdminBot::send_message('(IPN/' . $this->name . ') ' . $message);
            }
        }
    }
}

/**
 * Build an IPNResponse-shaped object from generic inputs so any gateway can feed
 * the existing handlers. $fields overlays the defaults; $option_pairs is a list
 * of [name, value] arrays that becomes option_name{i}/option_selection{i}, which
 * IPNResponse exposes as $obj->options[i] = [name, value] (the handlers read the
 * value at index [1]).
 *
 * @param array $fields        e.g. mc_gross, mc_fee, txn_id, payer_email, ...
 * @param array $option_pairs  e.g. [['Semester','AUTUMN'], ['Year','2026']]
 */
function build_ipn_response(array $fields, array $option_pairs = array()) {
    // Every key IPNResponse::__construct() reads unconditionally must exist or
    // PHP 8 emits undefined-key warnings. Default them all, then overlay.
    $defaults = array(
        'mc_gross' => '',
        'protection_eligibility' => '',
        'payer_id' => '',
        'payment_date' => date('c'),
        'payment_status' => 'Completed',
        'charset' => 'utf-8',
        'first_name' => '',
        'last_name' => '',
        'payer_email' => '',
        'mc_fee' => '0.00',
        'notify_version' => '',
        'custom' => '',
        'payer_status' => '',
        'business' => '',
        'verify_sign' => '',
        'txn_id' => '',
        'payment_type' => 'instant',
        'receiver_email' => '',
        'payment_fee' => '',
        'shipping_discount' => '0.00',
        'insurance_amount' => '0.00',
        'receiver_id' => '',
        'txn_type' => '',
        'item_name' => '',
        'discount' => '0.00',
        'mc_currency' => 'USD',
        'item_number' => '',
        'residence_country' => '',
        'shipping_method' => '',
        'transaction_subject' => '',
        'payment_gross' => '',
        'ipn_track_id' => '',
        'quantity' => '1',
    );

    $request = array_merge($defaults, $fields);

    $i = 1;
    foreach ($option_pairs as $pair) {
        $request["option_name{$i}"] = $pair[0];
        $request["option_selection{$i}"] = $pair[1];
        $i++;
    }

    return new IPNResponse($request);
}

/**
 * Dispatch a normalized payment to the correct handler, reusing handled_ipns for
 * idempotency. A replayed notification with the same $tx_id is a no-op.
 *
 * @param object $gateway       PaymentGatewayContext (getSandbox()).
 * @param IPNResponse $payment_info
 * @param array  $custom_obj    must contain 'source' (Source::DUES|Source::PRINTFUL).
 * @param string $tx_id         idempotency key (e.g. the Stripe session id).
 * @return bool  true if handled now, false if it was already handled.
 */
function process_normalized_payment($gateway, $payment_info, array $custom_obj, $tx_id) {
    $source = isset($custom_obj['source']) ? $custom_obj['source'] : null;

    $already = already_handled($tx_id);
    if ($already !== false) {
        payment_log("[{$tx_id}] ERROR: already handled on {$already[0]} by {$already[1]}");
        if (class_exists('AdminBot')) {
            AdminBot::send_message("(Payment) Duplicate notification for [{$tx_id}] ignored (already handled).");
        }
        return false;
    }

    // Reject an unknown source before claiming so we don't record a tx we can't
    // fulfil.
    if ($source !== Source::PRINTFUL && $source !== Source::DUES && $source !== Source::DONATION && $source !== Source::KIT) {
        payment_log("[{$tx_id}] Unhandled payment! Raw source: " . var_export($source, true));
        throw new IPNHandlerException("[{$tx_id}]: Unknown payment source: " . var_export($source, true));
    }

    // Claim the tx up-front: handled_ipns.txid is UNIQUE, so this is an atomic
    // mutex that stops two concurrent deliveries of the same payment from both
    // fulfilling. If the handler then fails, we RELEASE the claim and rethrow so
    // the caller returns a non-2xx and the gateway's retry can fulfil the order
    // — the handlers below are idempotent by txid, so a retry never double-fulfils.
    handled_tx($tx_id, $source);

    try {
        switch ($source) {
            case Source::PRINTFUL:
                payment_log("[{$tx_id}] Handling payment with the PRINTFUL handler");
                require_once(__DIR__ . '/handlers/printful.php');
                PRINTFUL\handle_payment_notification($gateway, $payment_info, $custom_obj);
                break;
            case Source::DUES:
                payment_log("[{$tx_id}] Handling payment with the DUES handler");
                require_once(__DIR__ . '/handlers/dues.php');
                DUES\handle_payment_notification($gateway, $payment_info, $custom_obj);
                break;
            case Source::DONATION:
                payment_log("[{$tx_id}] Handling payment with the DONATION handler");
                require_once(__DIR__ . '/handlers/donation.php');
                DONATION\handle_payment_notification($gateway, $payment_info, $custom_obj);
                break;
            case Source::KIT:
                payment_log("[{$tx_id}] Handling payment with the KIT handler");
                require_once(__DIR__ . '/handlers/kit.php');
                KIT\handle_payment_notification($gateway, $payment_info, $custom_obj);
                break;
        }
    } catch (\Throwable $e) {
        release_tx($tx_id);
        payment_log("[{$tx_id}] Handler failed, released claim for retry: " . $e->getMessage());
        throw $e;
    }

    return true;
}
