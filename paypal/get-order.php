<?php
require_once('../template/top.php');
require_once('../api/paypal/paypal.php');
require_once('../template/classes/currency.php');
global $db;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // read request body
    $request = json_decode(file_get_contents("php://input"),true);
    if(isset($request['order_id'])){
        //todo: get order stuff from this instead of making a new one
    }
    $item_names = $request['item_identifiers'];
    $return_url = $request['return_url'];
    $cancel_url = $request['cancel_url'];
    // combines the item names in a comma-delimited string for MySQL query
    $item_names_str = '';
    foreach ($item_names as $item_name) {
        $item_names_str .= "'{$db->real_escape_string($item_name)}',";
    }
    $item_names_str = rtrim($item_names_str, ",");

    // Queries the item info for items requested... match is based on item name
    $query = "SELECT 
                    item_type, sales_price, item_name, discount, discount_required_item
                FROM 
                    paypal_items 
                WHERE 
                    item_name in ({$item_names_str})";

    $q = $db->query($query);

    // send 500 if the query fails
    if ($q === false) {
        error_log("PayPal item db query failed: {$db->error}");
        http_response_code(500);
        die();
    }
    // list of PayPalItems
    $items = array();
    $subtotal = new Currency(0, 0);
    $discounts = [];
    // since we don't have sales tax nexus in the US, we assume tax = 0
    $r = $q->fetch_assoc();
    $physical_goods = ['printful_product'];
    $shipping_required = false;
    $item_types = ['dues' => 0, 'printful_product' => 0, 'donation' => 0];
    $required_discount_item_types = [];
    while ($r !== null) {
        $item_type = $r["item_type"];
        $item_types[$item_type]++;
        if (in_array($item_type, $physical_goods)) {
            $category = 'PHYSICAL_GOODS';
            $shipping_required = true;
        } else if ($item_type == 'dues') {
            $category = 'DIGITAL_GOODS';
        } else if ($item_type == 'donation') {
            $category = 'DONATION';
        } else {
            $category = null;
        }
        $items[] = new PayPalItem($r['item_name'],
            1,
            new PayPalCurrencyField('USD', $r['sales_price']),
            null,
            /*""*/ null, null, null, $category
        );
        $subtotal->add(Currency::from_string($r['sales_price']));
        if ($r['discount'] !== '0.00') {
            $discounts[] = Currency::from_string($r['discount']);
            $required_discount_item_types[] = $r['discount_required_item'];
        }

        $r = $q->fetch_assoc();
    }

    $discount = new Currency(0, 0);
    for ($i = 0; $i < count($discounts); $i++) {
        if ($item_types[$required_discount_item_types[$i]] > 0) {
            $discount->add($discounts[$i]);
            $item_types[$required_discount_item_types[$i]]--;
        }
    }
    // create order
    $p = new PayPalCustomApi();
    try {
        $order = json_decode($p->create_order(
            $items,
            (string)Currency::subtract_($subtotal, $discount),
            (string)$subtotal,
            new Currency(0,0),
            $shipping_required,
            $discount->is_zero() ? null : $discount),
        true);
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
        //  confirm order? I'm not sure when PayPal gives a no approve or payer-action link
        http_response_code(500);
        error_log('Could not find a redirect link: ' . $order);
        die();
    }
    // send 201 and return redirect link
    http_response_code(201);
    $response_body = new stdClass();
    $response_body->redirect_url = $redirect;
    echo json_encode($response_body);
    error_log("PayPal order created");
} else{
    http_response_code(403);
}