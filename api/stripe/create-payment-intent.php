<?php
/*
 * PaymentIntent creator for the inline Express Checkout Element (Apple Pay /
 * Google Pay / Link on the page itself, no redirect). The amount is always
 * recomputed server-side, exactly like create-checkout-session.php. Returns
 * {clientSecret}; the wallet payment is confirmed client-side and fulfilled by
 * the payment_intent.succeeded branch of webhook.php.
 */

require('../../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
require_once(BASE . '/api/stripe/vendor/autoload.php');

header('Content-Type: application/json');

function fail($http, $message) {
    header($http);
    echo json_encode(array('error' => $message));
    die();
}

if (empty(STRIPE_SECRET_KEY)) {
    AdminBot::send_message("(Stripe) STRIPE_SECRET_KEY is not configured.");
    fail('HTTP/1.1 500 Internal Server Error', 'Payments are not configured.');
}

\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$source = isset($_REQUEST['source']) ? $_REQUEST['source'] : '';

// Email the Express Checkout Element collected from the wallet (Apple/Google Pay
// don't reliably put an email on the charge). We stash it in metadata + set it as
// the PI's receipt_email so the webhook can always reach the buyer.
$buyer_email = (!empty($_REQUEST['email']) && filter_var($_REQUEST['email'], FILTER_VALIDATE_EMAIL)) ? $_REQUEST['email'] : '';
$receipt_email = $buyer_email !== '' ? $buyer_email : null;

try {
    if ($source === 'donation') {
        $amount = round((float) (isset($_REQUEST['amount']) ? $_REQUEST['amount'] : 0), 2);
        if ($amount < 1 || $amount > 10000) {
            fail('HTTP/1.1 400 Bad Request', 'Please enter a donation amount between $1 and $10,000.');
        }
        $custom = array('source' => 'DONATION');
        $item_name = 'UNT Robotics Donation';

        $intent = \Stripe\PaymentIntent::create(array(
            'amount' => intval(round($amount * 100)),
            'currency' => 'usd',
            'automatic_payment_methods' => array('enabled' => true),
            'description' => $item_name,
            'receipt_email' => $receipt_email,
            'metadata' => array(
                'source' => 'DONATION',
                'custom' => serialize($custom),
                'options' => json_encode(array()),
                'quantity' => '1',
                'item_name' => $item_name,
                'email' => $buyer_email,
            ),
        ));

        echo json_encode(array('clientSecret' => $intent->client_secret, 'needsShipping' => false));
    } else if ($source === 'merch') {
        require(BASE . '/api/printful/printful.php');
        require(BASE . '/template/functions/functions.php');

        $external_product_id = isset($_REQUEST['product']) ? $_REQUEST['product'] : '';
        $variant_id = isset($_REQUEST['variant']) ? $_REQUEST['variant'] : '';
        $quantity = isset($_REQUEST['quantity']) ? max(1, intval($_REQUEST['quantity'])) : 1;
        if (empty($external_product_id) || empty($variant_id)) {
            throw new RuntimeException("Missing product or variant.");
        }

        $printfulapi = new PrintfulCustomAPI();
        $product = $printfulapi->get_product('@' . $external_product_id);
        if ($product === null) {
            throw new RuntimeException("Unknown product.");
        }
        $selected_variant = null;
        foreach ($product->get_variants() as $variant) {
            if ($variant->get_id() == $variant_id) {
                $selected_variant = $variant;
                break;
            }
        }
        if ($selected_variant === null) {
            throw new RuntimeException("Unknown variant for this product.");
        }
        $catalog_product = $printfulapi->get_catalog_product($product->get_variants()[0]->get_product()->get_product_id());

        $unit_price = $selected_variant->get_price();
        $currency = $selected_variant->get_currency();
        if (empty($currency)) {
            $currency = $product->get_product_currency();
        }
        if (!is_numeric($unit_price) || $unit_price <= 0) {
            throw new RuntimeException("Unable to determine the product price.");
        }

        $variant_variant_name = preg_replace("@.* - (.+)$@i", "$1", $selected_variant->get_name());
        $custom = array(
            'source' => 'PRINTFUL_PRODUCT',
            'product' => $external_product_id,
            'variant' => $selected_variant->get_id(),
        );
        $merch_auth = auth();
        if (is_array($merch_auth) && isset($merch_auth[0]['id'])) {
            $custom['uid'] = $merch_auth[0]['id'];
        }
        $option_pairs = array(
            array('Type', $catalog_product->get_type_name()),
            array('Product', $product->get_name()),
            array('Variant', $variant_variant_name),
        );
        $item_name = $product->get_name() . ' - ' . $variant_variant_name;

        $intent = \Stripe\PaymentIntent::create(array(
            'amount' => intval(round($unit_price * $quantity * 100)),
            'currency' => strtolower($currency),
            'automatic_payment_methods' => array('enabled' => true),
            'description' => $item_name,
            'receipt_email' => $receipt_email,
            'metadata' => array(
                'source' => 'PRINTFUL_PRODUCT',
                'custom' => serialize($custom),
                'options' => json_encode($option_pairs),
                'quantity' => (string) $quantity,
                'item_name' => $item_name,
                'email' => $buyer_email,
            ),
        ));

        echo json_encode(array('clientSecret' => $intent->client_secret, 'needsShipping' => true));
    } else if ($source === 'dues') {
        $auth = auth();
        if (!$auth) {
            fail('HTTP/1.1 401 Unauthorized', 'You must be logged in to pay dues.');
        }
        $userinfo = $auth[0];

        $tshirt = !empty($_REQUEST['t-shirt']) ? $_REQUEST['t-shirt'] : false;
        $fullyear = filter_var(@$_REQUEST['full-year'], FILTER_VALIDATE_BOOLEAN);

        $q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
        if (!$q || $q->num_rows !== 2) {
            AdminBot::send_message("Unable to determine the dues payment price (stripe PI)");
            throw new RuntimeException("Unable to determine dues payment price");
        }
        $r = $q->fetch_all(MYSQLI_ASSOC);
        $mapped_config = array();
        array_walk($r, function (&$val, $_key) use (&$mapped_config) {
            $mapped_config[$val['key']] = $val['value'];
        });

        $t_shirt_dues_purchase_price = $mapped_config['t_shirt_dues_purchase_price'];
        $single_semester_dues_price = $mapped_config['semester_price'];
        $full_year_dues_price = $single_semester_dues_price * 2;
        $current_term = $untrobotics->get_current_term();
        $next_term = $untrobotics->get_next_term();

        $permit_full_year_payment = $current_term == Semester::AUTUMN;
        if (!$permit_full_year_payment && $fullyear) {
            throw new RuntimeException("Unable to pay for full year at this time");
        }

        $custom = array(
            'source' => 'DUES_PAYMENT',
            'uid' => $userinfo['id'],
            'include-tshirt' => $tshirt,
        );
        $cost = $fullyear ? $full_year_dues_price : $single_semester_dues_price;
        if ($tshirt) {
            $cost += $t_shirt_dues_purchase_price;
        }
        $n_semesters = $fullyear ? 2 : 1;

        $option_pairs = array(
            array('Semester', Semester::get_name_from_value($current_term)),
            array('Year', (string) $untrobotics->get_current_year()),
        );
        if ($fullyear) {
            $option_pairs[] = array('Semester1', Semester::get_name_from_value($next_term));
            $option_pairs[] = array('Year1', (string) $untrobotics->get_next_year());
        }
        $item_name = "UNT Robotics Dues (x{$n_semesters})" . ($tshirt ? " + T-shirt" : "");

        $intent = \Stripe\PaymentIntent::create(array(
            'amount' => intval(round($cost * 100)),
            'currency' => 'usd',
            'automatic_payment_methods' => array('enabled' => true),
            'description' => $item_name,
            'receipt_email' => $receipt_email,
            'metadata' => array(
                'source' => 'DUES_PAYMENT',
                'custom' => serialize($custom),
                'options' => json_encode($option_pairs),
                'quantity' => '1',
                'item_name' => $item_name,
                'email' => $buyer_email,
            ),
        ));

        echo json_encode(array('clientSecret' => $intent->client_secret, 'needsShipping' => $tshirt ? true : false));
    } else if ($source === 'kit') {
        // Inline wallet (Apple Pay / Google Pay) for the $40 Electronics Kit.
        // Phone required; email optional (falls back to the wallet's email).
        $first = trim((string) (isset($_REQUEST['first_name']) ? $_REQUEST['first_name'] : ''));
        $last  = trim((string) (isset($_REQUEST['last_name'])  ? $_REQUEST['last_name']  : ''));
        $phone = preg_replace('/\D/', '', (string) (isset($_REQUEST['phone']) ? $_REQUEST['phone'] : ''));
        if (strlen($phone) === 11 && $phone[0] === '1') { $phone = substr($phone, 1); }
        if ($first === '' || $last === '' || strlen($phone) < 10) {
            fail('HTTP/1.1 400 Bad Request', 'Please enter your first name, last name, and a valid phone number.');
        }
        $email = strtolower($buyer_email); // validated above (form field or wallet email), or ''
        $existing = $db->query('SELECT id FROM kit_preorders WHERE phone = "' . $db->real_escape_string($phone) . '" AND refunded = 0 LIMIT 1');
        if ($existing && $existing->num_rows > 0) {
            fail('HTTP/1.1 409 Conflict', 'It looks like you have already preordered a kit with this phone number.');
        }
        $custom = array('source' => 'KIT_PREORDER', 'first_name' => $first, 'last_name' => $last, 'phone' => $phone, 'email' => $email);
        $item_name = 'UNT Robotics Electronics Kit (preorder)';

        $intent = \Stripe\PaymentIntent::create(array(
            'amount' => 4000,
            'currency' => 'usd',
            'automatic_payment_methods' => array('enabled' => true),
            'description' => $item_name,
            'receipt_email' => $receipt_email,
            'metadata' => array(
                'source' => 'KIT_PREORDER',
                'custom' => serialize($custom),
                'options' => json_encode(array()),
                'quantity' => '1',
                'item_name' => $item_name,
                'email' => $email,
            ),
        ));

        echo json_encode(array('clientSecret' => $intent->client_secret, 'needsShipping' => false));
    } else {
        fail('HTTP/1.1 400 Bad Request', 'Unknown payment source.');
    }
} catch (Exception $ex) {
    AdminBot::send_message("(Stripe) Failed to create payment intent ({$source}): " . $ex->getMessage());
    fail('HTTP/1.1 500 Internal Server Error', 'Unable to start payment. Please try again.');
}
