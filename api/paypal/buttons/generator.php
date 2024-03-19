<?php
require('../../../template/top.php');
require(BASE . '/template/functions/payment_button.php');
require(BASE . '/api/discord/bots/admin.php');

$auth = auth();

if ($auth) {
    $userinfo = $auth[0];

    $tshirt = !empty($_GET['t-shirt']) ? $_GET['t-shirt'] : false;
    $fullyear = filter_var(@$_GET['full-year'], FILTER_VALIDATE_BOOLEAN);

    $q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
    if (!$q || $q->num_rows !== 2) {
        AdminBot::send_message("Unable to determine the dues payment price (generator)");
        throw new RuntimeException("Unable to determine dues payment price (generator)");
    }

    $r = $q->fetch_all(MYSQLI_ASSOC);

    $mapped_config = array();
    array_walk(
        $r,
        function(&$val, $_key) use (&$mapped_config)
        {
            $mapped_config[$val['key']] = $val['value'];
        }
    );

    $t_shirt_dues_purchase_price = $mapped_config['t_shirt_dues_purchase_price'];
    $single_semester_dues_price = $mapped_config['semester_price'];
    $full_year_dues_price = $single_semester_dues_price * 2;
    $current_term = $untrobotics->get_current_term();
    $next_term = $untrobotics->get_next_term();

    // only allow the user to pay for the full year it is the autumn semester
    $permit_full_year_payment = $current_term == Semester::AUTUMN;

    if (!$permit_full_year_payment && $fullyear) {
        AdminBot::send_message("Someone is trying to pay for the full year (dues) in the Spring semester");
        throw new RuntimeException("Unable to pay for full year at this time");
    }

    $custom = serialize(array(
        'source' => 'DUES_PAYMENT',
        'uid' => $userinfo['id'],
        'include-tshirt' => $tshirt
    ));

    $cost = 0;
    if ($fullyear) {
        $cost += $full_year_dues_price;
    } else {
        $cost += $single_semester_dues_price;
    }

    if ($tshirt) {
        $cost += $t_shirt_dues_purchase_price;
    }

    $n_semesters = $fullyear ? 2 : 1;

    $items = array();
    $amounts = array();
    $item = "UNT Robotics Dues (x{$n_semesters})" . ($tshirt ? " + T-shirt" : "");
    $amount = $cost;

    $payment_button = new PaymentButton(
        $item,
        $amount,
        'Pay Now',
        'Complete Dues Payment'
    );

    $payment_button->set_custom($custom);

    if ($fullyear) {
        $payment_button->set_opt_names(
            array(
                'Semester',
                'Year',
                'Semester1',
                'Year1'
            )
        );
        $payment_button->set_opt_vals(
            array(
                Semester::get_name_from_value($current_term),
                $untrobotics->get_current_year(),
                Semester::get_name_from_value($next_term),
                $untrobotics->get_next_year()
            )
        );
    } else {
        $payment_button->set_opt_names(
            array(
                'Semester',
                'Year'
            )
        );
        $payment_button->set_opt_vals(
            array(
                Semester::get_name_from_value($current_term),
                $untrobotics->get_current_year()
            )
        );
    }

    $payment_button->set_complete_return_uri('/dues/paid');
    $payment_button->set_cancel_return_uri('/dues/');

    $button = $payment_button->get_button();

    header('Content-type: application/json');
    $response = new stdClass();
    $response->fullYear = $fullyear;
    if ($button->error === false) {
        $response->button = $payment_button->get_button()->button;
        $response->cost = $cost;
    } else {
        AdminBot::send_message("Failed to load dues payment button: " . $button->error);
        $response->button = '<div class="alert alert-danger">An error occurred loading the payment button...</div>';
        $response->cost = -1;
    }
    echo json_encode($response);
} else {
    header("HTTP/1.1 401 Unauthorized");
    die();
}
?>
