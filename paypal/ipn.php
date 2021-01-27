<?php
$log = var_export($_REQUEST, true);

function payment_log($message) {
	file_put_contents('logs/ipn.log', '[' . date('c', time()) . '] ' . $message . PHP_EOL, FILE_APPEND);
}

require('ipn/IPNVerify.php');
require('ipn/IPNResponse.php');
require('../template/top.php');
require(BASE . '/api/discord/bots/admin.php');

function handled_tx($tx_id, $source) {
	global $db;
	if (!$db->query('INSERT INTO handled_ipns (txid, handled_source) VALUES ("' . $db->real_escape_string($tx_id) . '", "' . $db->real_escape_string($source) . '")')) {
		throw new Exception("Unable to mark the transaction as handle in the database");
	}
}

function already_handled($tx_id) {
	global $db;
	$q = $db->query('SELECT handled_timestamp, handled_source FROM handled_ipns WHERE txid = "' . $db->real_escape_string($tx_id) . '"');
	if ($q) {
		if ($q->num_rows > 0) {
			$row = $q->fetch_row();
			return $row;
		}
	} else {
		throw new Exception("Failed to retrieve handled transaction IDs from the database");
	}
	return false;
}

$ipn = new PaypalIPN();

$payer_email = $_POST['payer_email'];
$payer_id = $_POST['payer_id'];
$txn_id = $_POST['txn_id'];
if ($payer_email == 'unt.robotics-buyer@unt.edu' && $payer_id == 'XKVTP2Y84G8ZU') {
	$ipn->useSandbox();
}

//log_payment($invoice_id, 'INFO: IPN, SANDBOX: ' . (is_sandbox() ? 'true' : 'false'));

class Source {
	const PRINTFUL = 'PRINTFUL_PRODUCT';
	const DUES = 'DUES_PAYMENT';
}

class IPNHandlerException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
	}
}

$verified = $ipn->verifyIPN();
if ($verified) {
	try {
		// verified
		payment_log("[{$txn_id}] VALID (is sandbox: {$ipn->getSandbox()})");
		payment_log(var_export($_REQUEST, true));

		$custom = $_POST['custom'];

		$source = null;
		if ($custom_obj = @unserialize($custom)) {
			$source = $custom_obj['source'];
		} else {
			$source = $custom;
		}

		$payment_info = new IPNResponse($_POST);

		$already_handled = already_handled($txn_id);
		if ($already_handled === false) {
			//handled_tx($txn_id, $source);
			payment_log("[{$txn_id}] Not yet handled");
		} else {
			payment_log("[{$txn_id}] ERROR: already handled on {$already_handled[0]} by {$already_handled[1]}");
			AdminBot::send_message("(IPN) Exception: Received duplicate notification for [{$txn_id}]. This suggests an earlier handling attempt failed.");
			die();
		}

		// call the correct handler based on the source of the request
		try {
		switch ($source) {
			case Source::PRINTFUL:
				payment_log("[{$txn_id}] Handling IPN with the PRINTFUL handler");
				require_once('./ipn/handlers/printful.php');

				handled_tx($txn_id, $source);
				handle_payment_notification($ipn, $payment_info, $custom_obj);
				break;
			case Source::DUES:
				payment_log("[{$txn_id}] Handling IPN with the DUES handler");			
				require_once('./ipn/handlers/dues.php');

				handled_tx($txn_id, $source);
				handle_payment_notification($ipn, $payment_info, $custom_obj);
				break;
			default:
				payment_log("[{$txn_id}] Unhandled IPN! Raw source: {$source}");	
				// unhandled! this is bad
				// perhaps we should notify the discord of this error
		}
		} catch (Exception $ex) {
			payment_log("[{$txn_id}] ERROR:\n" . $ex);
			throw $ex;
		}
	} catch (Exception $ex) {
		AdminBot::send_message("(IPN) Exception: Failed to process [{$txn_id}]: {$ex}");
	}
} else {
	// NOT verified
	payment_log("[{$txn_id}] INVALID (is sandbox: {$ipn->getSandbox()})");
}