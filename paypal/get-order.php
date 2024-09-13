<?php
require_once('../template/top.php');
require_once('../api/paypal/paypal.php');
require_once('../template/classes/currency.php');
global $db;
if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // read request body
    $request = json_decode(file_get_contents("php://input"),true);
    if(isset($request['order_id'])){
        $p = new PayPalCustomApi();
        try {
            $response = json_decode($p->get_order_info($request['order_id']), true);
        } catch(PayPalCustomApiException $e){
            http_response_code(404);
            error_log("Could not find order with ID {$request['order_id']}: {$e->getMessage()}");
            die();
        }
        http_response_code(200);

    }

    $item_names = $request['item_identifiers'];

    // combines the item names in a comma-delimited string for MySQL query
    $item_names_str = '';
    foreach ($item_names as $item_name) {
        $item_names_str .= "'{$db->real_escape_string($item_name)}',";
    }
    $item_names_str = rtrim($item_names_str, ",");

    // Queries the item info for items requested... match is based on item name
    $query = "SELECT 
                    id, item_type, sales_price, item_name, discount, discount_required_item
                FROM 
                    paypal_items 
                WHERE 
                    item_name in ({$item_names_str})";
    $q = $db->query($query);

    // send 500 if the query fails
    if ($q === false) {
        http_response_code(500);
        error_log("PayPal item db query failed: {$db->error}");
        die();
    }
    // list of PayPalItems
    $items = array();
    // subtotal of order
    $subtotal = new Currency(0, 0);
    // any discounts that might be applicable
    $discounts = [];
    // item IDs in the paypal_items table
    $item_ids = [];
    $custom_data = [];


    $r = $q->fetch_assoc();
    $physical_goods = ['printful_product'];
    $shipping_required = false;
    $item_type_count = ['dues' => 0, 'printful_product' => 0, 'donation' => 0];
    $required_discount_item_types = [];

    global $untrobotics;
    $custom = null;
    // keep fetching next row until no more
    while ($r !== null) {
        $item_ids[] = $r['id'];
        $item_type = $r["item_type"];
        $item_type_count[$item_type]++;

        if (in_array($item_type, $physical_goods)) {


            $category = 'PHYSICAL_GOODS';
            $shipping_required = true;

            // add custom data stuff for Printful or whatever

            $custom = new stdClass();


        } else if ($item_type == 'dues') {
            $custom = new stdClass();

            $category = 'DIGITAL_GOODS';

            // get number of semesters for dues from item name
            $semester_n = null;
            preg_match('[0-9]+', $r['item_name'],$semester_n);
            $custom->semester_n = $semester_n[0];

            // set semester and year for the dues
            $custom->semester = $untrobotics->get_current_term();
            $custom->year = $untrobotics->get_current_year();

        } else if ($item_type == 'donation') {
            $category = 'DONATION';
        } else {
            $category = null;
        }
        $custom_data[] = json_encode($custom);
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

        $custom = null;
        $r = $q->fetch_assoc();
    }

    // prevent order with dues from being created if user isn't logged in
    if($item_type_count['dues']>0){
        $auth = auth();
        if (!$auth){
            http_response_code(403);
            die();
        }
    }

    $discount = new Currency(0, 0);
    for ($i = 0; $i < count($discounts); $i++) {
        if ($item_type_count[$required_discount_item_types[$i]] > 0) {
            $discount->add($discounts[$i]);
            $item_type_count[$required_discount_item_types[$i]]--;
        }
    }

    $p = new PayPalCustomApi();
    try {
        // create order
        $order = json_decode($p->create_order(
            $items,
            (string)Currency::subtract_($subtotal, $discount),
            (string)$subtotal,
            new Currency(0,0),
            $shipping_required,
            $discount->is_zero() ? null : $discount, $request['return_url'], $request['cancel_url']),
        true);
    } catch (Exception $ex) {
        http_response_code(500);
        error_log("Failed to create order: {$ex->getMessage()}");
        die();
    }

    // add order to db
    if(isset($auth)){ // should only be set when dues are purchased
        $query = "INSERT INTO paypal_orders(uid, paypal_order_id) VALUES('{$auth[0]['id']}', '{$db->real_escape_string($order['id'])}')";
    } else {
        $query = "INSERT INTO paypal_orders(paypal_order_id) VALUES('{$db->real_escape_string($order['id'])}')";
    }
    $q = $db->query($query);
    if($q === false){
        http_response_code(500);
        error_log("Error trying to add an order to the db: {$db->error}");
        die();
    }

    // get paypal_orders ID (not PayPal's ID of the order) for each paypal_order_item
    $q = $db->query("SELECT id FROM paypal_orders WHERE paypal_order_id='{$db->real_escape_string($order['id'])}'");
    if($q === false){
        http_response_code(500);
        error_log("Error trying to fetch ID of paypal order from the db: {$db->error}");
        die();
    }
    $db_order_id = $q->fetch_assoc()['id'];

    for($i = 0; $i < count($item_ids); $i++){
        if($custom_data[$i]!=='null'){
            $query = "INSERT INTO paypal_order_item (item_id,order_id, custom_data) VALUES({$item_ids[$i]}, {$db_order_id}, '{$db->real_escape_string($custom_data[$i])}')";
        } else{
            $query = "INSERT INTO paypal_order_item (item_id,order_id) VALUES({$item_ids[$i]}, {$db_order_id})";
        }
        $q = $db->query($query);
        if($q === false){
            error_log("Error adding item to order {$order['id']}, internal ID {$db_order_id}. Item ID {$item_ids[$i]}: {$db->error}");
            http_response_code(500);
            die();
        }
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
    http_response_code(201);
    $response_body = new stdClass();
    $response_body->redirect_url = $redirect;
    echo json_encode($response_body);
} else{
    http_response_code(403);
}