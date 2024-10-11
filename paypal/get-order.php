<?php
require_once('../template/top.php');
require_once('../api/paypal/paypal.php');
require_once('../template/classes/currency.php');
require_once('../api/discord/bots/admin.php');
global $db;


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // read request body
//    $request = $_POST;

    $items = $_POST['items'];
    if(count($items)<1){
        http_response_code(400);
        die();
    }

    $return_url = "https://{$_SERVER['SERVER_NAME']}/" . htmlspecialchars_decode($_POST['return_uri']);
    $cancel_url = "https://{$_SERVER['SERVER_NAME']}/" . htmlspecialchars_decode($_POST['cancel_uri']);
    $paypal_items = [];

    $subtotal = new Currency(0, 0);
    $discount = new Currency(0, 0);
    $shipping_required = false;

    // create paypal items and calculate subtotal and discount
    foreach ($items as $item) {
        switch ($item['type']) {
            case 'dues':
            {
                $dues = true;

                // ensure the user is logged in for dues
                $auth = auth();
                if (!$auth) {
                    http_response_code(401);
                    die();
                }

                global $untrobotics;
                $userinfo = $auth[0];

                // get prices
                $dues_prices = $untrobotics->get_dues_prices('Get order');
                $t_shirt_dues_purchase_price = $dues_prices['t_shirt_dues_purchase_price'];
                $single_semester_dues_price = $dues_prices['semester_price'];
                $full_year_dues_price = $single_semester_dues_price * 2;

                // get term and years for data fields
                $current_term = $untrobotics->get_current_term();
                $next_term = $untrobotics->get_next_term();

                $current_year = $untrobotics->get_current_year();
                $next_year = $untrobotics->get_next_year();

                // only allow the user to pay for the full year it is the autumn semester
                $permit_full_year_payment = $current_term == Semester::AUTUMN;

                // represents whether the payment is for full year dues
                $fullyear = $item['full_year'] === 'true';

                // represents whether a dues t-shirt was included
                $dues_with_tshirt = $item['t-shirt'] === 'true';

                // don't allow orders asking for full year payments if it's not the fall semester
                if (!$permit_full_year_payment && $fullyear) {
                    AdminBot::send_message("Someone is trying to pay for the full year (dues) in the Spring semester");
                    throw new RuntimeException("Unable to pay for full year at this time");
                }

                /** $data is passed to the SKU field so we can process the order info
                 * Dues format:
                 * [
                 *  's' => $current_term, // Should be one of the Semester const. Should be the first semester paid for (i.e., fall if paying for full year)
                 *  'y' => $current_year, // Should be whatever $untrobotics->get_current_year() returns. Should be the year for the first semester
                 *  's1' => $next_term, // Used if paying for full year
                 *  'y1' => $next_year, // Used if paying for full year
                 *  't' => true // Use if tshirt is purchased
                 * ]
                 * */
                $data = ['s' => $current_term, 'y' => $current_year];

                $cost = 0;
                if ($fullyear) {
                    $cost += $full_year_dues_price;
                    $data['s1'] = $next_term;
                    $data['y1'] = $next_year;
                } else {
                    $cost += $single_semester_dues_price;
                }
                $subtotal->add(new Currency($cost));

                if ($dues_with_tshirt) {
                    $data['t'] = true;
                }

                $n_semesters = $fullyear ? 2 : 1;
                $item_name = 'UNT Robotics Dues - ';

                $item_name .= Semester::get_name_from_value($current_term) . ' ' . $current_year;
                if ($fullyear) {
                    $item_name .= ' & ' . Semester::get_name_from_value($next_term) . ' ' . $next_year;
                }

                /**
                 * The UPC field is the UID. The customer can't see the UPC field as far as I know
                 */
                $paypal_items[] = new PayPalItem($item_name, '1', new PayPalCurrencyField('USD', strval($cost)), null, 'UNT Robotics Dues', json_encode($data), null, 'DIGITAL_GOODS', null, new PayPalUPCField('UPC-A', str_pad(strval($userinfo['id']), 12, '0', STR_PAD_LEFT)));


                break;
            }
            case 'printful':
            {
                if($item['variant_id']==='') break;
                require_once('../api/printful/printful.php');
                $shipping_required = true;
                $printful_api = new PrintfulCustomAPI();

                // for retrieving prices from printful
                $variant = $printful_api->get_variant($item['variant_id']);

                // for the product url
                $parent_product = $printful_api->get_product('@' . $item['ext_id']);

                if ($variant === null || $parent_product === null) {
                    http_response_code(400);
                    error_log("Could not find Printful variant or parent with variant ID {$item['id']} and external ID {$item['ext_id']} when trying to create a PayPal order.");
                    die();
                }

                $price = $variant->get_internal_price() ?? $variant->get_price();

                // add price to subtotal
                $subtotal->add(Currency::from_string($price));

                // add discount if dues payment was with shirt and the printful product matches one of the dues shirts' IDs
                if(isset($dues_with_tshirt)&&$dues_with_tshirt && in_array($variant->get_external_id(), ['632b8e41a865f1','632b8e41a86664','632b8e41a866a1','632b8e41a866e2','632b8e41a86724','632b8e41a86761','632b8e41a867a9','632b8e41a867e6'])){
                    // discount = (list price - dues selling price)
                    $discount->add(Currency::from_string($price));
                    /** @noinspection PhpUndefinedVariableInspection */
                    $discount->subtract(Currency::from_string($t_shirt_dues_purchase_price));

                }

                $data = ['id'=>$item['variant_id']];

                $product_url = "https://{$_SERVER['SERVER_NAME']}/product/{$item['ext_id']}/{$parent_product->get_name()}/{$variant->get_product()->get_variant_id()}";
                $image_url = $variant->get_file_by_type(PrintfulVariantFilesTypes::PREVIEW);
                if($image_url !== null){
                    $image_url = $image_url->get_url()? $image_url->get_url() : $image_url->get_preview_url();
                }
                $currency_code = $variant->get_currency()?? 'USD';
                $paypal_items[] = new PayPalItem(mb_strimwidth($variant->get_name(),0,127,'...'),'1',new PayPalCurrencyField($currency_code,$price),null, null, json_encode($data), $product_url, 'PHYSICAL_GOODS',$image_url);

                break;
            }
            case 'donation':
            {
                $amount = Currency::from_string($item['amount']);

                // ensure amount is positive
                if ($amount->lt(new Currency('0'))) {
                    http_response_code(400);
                    die();
                }

                $subtotal->add($amount);

                $data = null;

                $paypal_items[] = new PayPalItem('Donation', '1', new PayPalCurrencyField('USD', $amount), null, 'Donation to UNT Robotics', $data, null, 'DONATION');
                break;
            }
            default:
            {
                http_response_code(400);
                error_log('Received unknown item when trying to make a PayPal order: ' . var_export($item, true));
                die();
            }
        }
    }
    // make PayPal order
    $p = new PayPalCustomApi();
    try {
        // create order
        $order = ($p->create_order(
            $paypal_items,
            (string)Currency::subtract_($subtotal, $discount),
            (string)$subtotal,
            new Currency(0, 0),
            $shipping_required,
            $discount->is_zero() ? null : $discount,
            $return_url, $cancel_url));

        // throw error if order is null
        if (!isset($order)) {
            throw new Exception("Couldn't retrieve access token.");
        } else if ($order === false) {  // throw error if no order was made
            throw new Exception('No items found in order');
        }
        $order = json_decode($order, true);
    } catch (Exception $ex) {
        http_response_code(500);
        error_log("Failed to create order: {$ex->getMessage()}");
        die();
    }


    // get the checkout link
    foreach ($order['links'] as $link) {
        if ($link['rel'] == 'payer-action' || $link['rel'] == 'approve') {
            $redirect = $link['href'];
            break;
        }
    }

    // if no checkout link found, send 500
    if (!isset($redirect)) {
        //  confirm order? I'm not sure when PayPal gives no approve/payer-action link
        http_response_code(500);
        error_log('Could not find a redirect link: ' . $order);
        die();
    }
    // send 201 and return redirect link
    header('Location: ' . $redirect);
} else {
    http_response_code(405);
}

