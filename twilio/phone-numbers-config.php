<?php
// Officer phone numbers for the Twilio IVR (twilio/process-incoming-call.php).
//
// The numbers are loaded from the PHONE_NUMBERS_JSON environment secret so no
// real numbers ever live in git or the image. This file is deliberately a
// SEPARATE require (not template/config.php): PHONE_NUMBERS is only defined for
// the Twilio call scripts that include it, not in the global scope of every
// page — so a leak elsewhere on the site can't enumerate officer numbers.
//
// PHONE_NUMBERS_JSON is a JSON object of role => E.164 number, e.g.
//   {"President":"+1469...","VicePresident":"+1682...",
//    "FinancialDirector":"+1817...","DirectorOfOperations":"+1512..."}
// Keys MUST match the PHONE_NUMBERS['...'] references in process-incoming-call.php.

$__phone_numbers = json_decode(getenv('PHONE_NUMBERS_JSON') ?: '{}', true);
if (!is_array($__phone_numbers)) {
    $__phone_numbers = array();
}
define('PHONE_NUMBERS', $__phone_numbers);
unset($__phone_numbers);
