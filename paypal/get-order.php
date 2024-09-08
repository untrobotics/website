<?php
require_once('../template/top.php');
require_once('../api/paypal/paypal.php');
require_once('../template/classes/currency.php');
global $db;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // read request body
    $request = json_decode(file_get_contents("php://input"));
    $item_names = $request['item_identifiers'];

    // combines the item names in a comma-delimited string for MySQL query
    $item_names_str = '';
    foreach ($item_names as $item_name) {
        $item_names_str .= "'{$db->real_escape_string($item_name)}',";
    }
    $item_names_str = rtrim($item_names_str, ",");

    // Queries the item info for items requested... match is based on item name
    $query = "SELECT 
                    item_type, sales_price, item_name 
                FROM 
                    paypal_items 
                WHERE 
                    item_name in {$item_names_str}";

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
    // since we don't have sales tax nexus in the US, we assume tax = 0
    $r = $q->fetch_assoc();
    $physical_goods = ['printful_product'];
    $shipping_required = false;
    while ($r !== null) {
        $item_type = $r["item_type"];
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
        $r = $q->fetch_assoc();
    }

    $p = new PayPalCustomApi();
    $order = json_decode($p->create_order($items, (string)$subtotal, (string)$subtotal, $shipping_required), true);
    foreach ($order['links'] as $link) {
        if ($link['rel'] == 'payer-action' || $link['rel'] == 'approve') {
            $redirect = $link['href'];
            break;
        }
    }
    if (!isset($redirect)) {
        //  confirm order? I'm not sure when PayPal gives a no approve or payer-action link
        http_response_code(500);
        die();
    }

    http_response_code(201);
    echo "{{\"redirect_url\":\"$redirect\"}}";
}
