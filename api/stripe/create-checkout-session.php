<?php
/**
 * Stripe Checkout session creator (Card / Apple Pay).
 *
 * Mirrors api/paypal/buttons/generator.php (DUES) and merch/product.php (MERCH)
 * but the price is ALWAYS recomputed server-side — the client only sends which
 * options it wants, never an amount. Returns JSON {url} for the hosted Checkout
 * page. Apple Pay rides on payment_method_types:['card'] once the domain is
 * registered in the Stripe dashboard.
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
$origin = WEBSITE_URL;

try {
    if ($source === 'dues') {
        // Dues require a logged-in member (merch checkout stays guest-friendly,
        // matching the old PayPal flow).
        $auth = auth();
        if (!$auth) {
            fail('HTTP/1.1 401 Unauthorized', 'You must be logged in to pay dues.');
        }
        $userinfo = $auth[0];

        // ---- DUES: recompute amount/terms/custom server-side (mirror generator.php)
        $tshirt = !empty($_REQUEST['t-shirt']) ? $_REQUEST['t-shirt'] : false;
        $fullyear = filter_var(@$_REQUEST['full-year'], FILTER_VALIDATE_BOOLEAN);

        $q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
        if (!$q || $q->num_rows !== 2) {
            AdminBot::send_message("Unable to determine the dues payment price (stripe)");
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

        // only allow paying for the full year during the autumn semester
        $permit_full_year_payment = $current_term == Semester::AUTUMN;
        if (!$permit_full_year_payment && $fullyear) {
            AdminBot::send_message("Someone is trying to pay for the full year (dues, stripe) in the Spring semester");
            throw new RuntimeException("Unable to pay for full year at this time");
        }

        $custom = array(
            'source' => 'DUES_PAYMENT',
            'uid' => $userinfo['id'],
            'include-tshirt' => $tshirt
        );

        $cost = $fullyear ? $full_year_dues_price : $single_semester_dues_price;
        if ($tshirt) {
            $cost += $t_shirt_dues_purchase_price;
        }
        $n_semesters = $fullyear ? 2 : 1;

        // Option pairs encode the semester/year exactly like the PayPal button so
        // the DUES handler reads them unchanged ([0]=Semester, [1]=Year, ...).
        $option_pairs = array(
            array('Semester', Semester::get_name_from_value($current_term)),
            array('Year', (string) $untrobotics->get_current_year())
        );
        if ($fullyear) {
            $option_pairs[] = array('Semester1', Semester::get_name_from_value($next_term));
            $option_pairs[] = array('Year1', (string) $untrobotics->get_next_year());
        }

        $item_name = "UNT Robotics Dues (x{$n_semesters})" . ($tshirt ? " + T-shirt" : "");

        $session_params = array(
            'mode' => 'payment',
            'payment_method_types' => array('card'),
            'line_items' => array(array(
                'price_data' => array(
                    'currency' => 'usd',
                    'product_data' => array('name' => $item_name),
                    'unit_amount' => intval(round($cost * 100)),
                ),
                'quantity' => 1,
            )),
            'success_url' => $origin . '/dues/paid?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $origin . '/dues/',
            'metadata' => array(
                'source' => 'DUES_PAYMENT',
                'custom' => serialize($custom),
                'options' => json_encode($option_pairs),
                'quantity' => '1',
                'item_name' => $item_name,
            ),
        );

        // A dues t-shirt is shipped, so collect a US shipping address for it.
        if ($tshirt) {
            $session_params['shipping_address_collection'] = array('allowed_countries' => array('US'));
            $session_params['phone_number_collection'] = array('enabled' => true);
        }

    } else if ($source === 'merch') {
        // ---- MERCH: recompute price from Printful (mirror merch/product.php)
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

        // Price recomputed from Printful — exactly what the PRINTFUL handler will
        // re-validate via get_variant($custom['variant'])->get_price().
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
            'variant' => $selected_variant->get_id()
        );

        // Merch checkout stays guest-friendly, but if the buyer is logged in,
        // tag the order with their uid so it can be associated to their account.
        $merch_auth = auth();
        if (is_array($merch_auth) && isset($merch_auth[0]['id'])) {
            $custom['uid'] = $merch_auth[0]['id'];
        }

        // Type/Product/Variant option pairs — the PRINTFUL handler reads
        // options[0]=Type, options[1]=Product, options[2]=Variant.
        $option_pairs = array(
            array('Type', $catalog_product->get_type_name()),
            array('Product', $product->get_name()),
            array('Variant', $variant_variant_name)
        );

        $item_name = $product->get_name() . ' - ' . $variant_variant_name;

        $session_params = array(
            'mode' => 'payment',
            'payment_method_types' => array('card'),
            'line_items' => array(array(
                'price_data' => array(
                    'currency' => strtolower($currency),
                    'product_data' => array('name' => $item_name),
                    'unit_amount' => intval(round($unit_price * 100)),
                ),
                'quantity' => $quantity,
            )),
            'shipping_address_collection' => array('allowed_countries' => array('US')),
            'phone_number_collection' => array('enabled' => true),
            'success_url' => $origin . '/merch/buy/complete?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $origin . '/merch/product/' . rawurlencode($external_product_id),
            'metadata' => array(
                'source' => 'PRINTFUL_PRODUCT',
                'custom' => serialize($custom),
                'options' => json_encode($option_pairs),
                'quantity' => (string) $quantity,
                'item_name' => $item_name,
            ),
        );

    } else if ($source === 'donation') {
        // ---- DONATION: variable amount chosen by the donor (nothing to recompute).
        $amount = round((float) (isset($_REQUEST['amount']) ? $_REQUEST['amount'] : 0), 2);
        if ($amount < 1 || $amount > 10000) {
            fail('HTTP/1.1 400 Bad Request', 'Please enter a donation amount between $1 and $10,000.');
        }
        $custom = array('source' => 'DONATION');
        $item_name = 'UNT Robotics Donation';
        $session_params = array(
            'mode' => 'payment',
            'payment_method_types' => array('card'),
            'line_items' => array(array(
                'price_data' => array(
                    'currency' => 'usd',
                    'product_data' => array('name' => $item_name),
                    'unit_amount' => intval(round($amount * 100)),
                ),
                'quantity' => 1,
            )),
            'success_url' => $origin . '/sponsorships/donate/thank-you',
            'cancel_url' => $origin . '/sponsorships',
            'metadata' => array(
                'source' => 'DONATION',
                'custom' => serialize($custom),
                'options' => json_encode(array()),
                'quantity' => '1',
                'item_name' => $item_name,
            ),
        );

    } else {
        fail('HTTP/1.1 400 Bad Request', 'Unknown payment source.');
    }

    $session = \Stripe\Checkout\Session::create($session_params);

    echo json_encode(array('id' => $session->id, 'url' => $session->url));
} catch (Exception $ex) {
    AdminBot::send_message("(Stripe) Failed to create checkout session ({$source}): " . $ex->getMessage());
    fail('HTTP/1.1 500 Internal Server Error', 'Unable to start checkout. Please try again.');
}
