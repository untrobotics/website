<?php
// this file contains various helper functions

/**
 * Gets a PayPal payment button. To change the items dynamically, set the value of input#paypal-items<index><key> (e.g., input#paypal-items0type.value = 'dues')
 * @param string $text Text to display on the button
 * @param array[] $items Array of items to be purchased. Format: [['type':'dues', 'full_year':true, 't-shirt':false], ['type':'printful','ext_id':'externalid' , 'variant_id':'variantid'], ['type':'donation', 'amount':'99.99'], ...]
 * @param string $return_uri URI to return the user to after order approval
 * @param string $cancel_uri URI to return the user to after order cancellation
 * @param null|string $form_elements Extra form elements to append before the button. 'name' should be set to items[<index>][<key>] e.g., items[0]['full_year']. Do not include an index-key here and in $items
 * @return void
 */
function get_payment_button(string $text, array $items, string $return_uri, string $cancel_uri, ?string $form_elements = null) {
    //https://' . "{$_SERVER['SERVER_NAME']}/{$return_uri}
	$items_json = json_encode($items);
	if(strlen($items_json) > 127){
		error_log("Items array for PayPal order is too large. Array: ${items_json}");
		return;
	}
	echo '<form method="post" action="'. "https://{$_SERVER['SERVER_NAME']}/paypal/get-order" . '">' .
            $form_elements .
            '<div class="paypal-button-container">
				<div class="paypal-button-overlay">
					<img src="/images/paypal-button.png" alt="">
					<input type="submit" id="buy-product-now" class="btn btn-primary" value="' . $text . '">';
                    foreach($items as $i=>$item){
                        foreach($item as $k=>$v){
                            echo '<input type="hidden" id="paypal-items' . $i . $k . '" name="items[' . $i . '][' . $k . ']" value="' . htmlspecialchars(trim(json_encode($v),'"')) . '">';
                        }
                    }

    echo            '<input type="hidden" name="return_uri" value="' . $return_uri . '"/>
					<input type="hidden" name="cancel_uri" value="' . $cancel_uri . '"/>
				</div>
            </div>
        </form>';
}

/**
 * Emails a receipt for a PayPal order
 * @param $paypal_order_info array The JSON-decoded response for get_order PayPal API call. This should be an associative array
 * @param $printful_order_info array[] An array containing information about the Printful order. Each index represents one Printful order. The required information is "variant_id" and "shipping_service".
 * @param $has_dues bool A boolean representing whether this order has dues or not. Defaults to false
 * @param $send_email bool Whether to send the email or not. If true, the email will be sent via SendGrid. If false, the email subject and body will be output in a string[]
 * @return bool|string[] A string array with the email subject, body, and recipient email if $send_email is false. Otherwise, true if the email received an HTTP success code. False otherwise. See {@see email()} for more information
 */
function email_receipt(array &$paypal_order_info, ?array $printful_order_info, bool $has_dues = false, bool $send_email = true) {
    $payer = $paypal_order_info['payer'];
    $payment_capture = $paypal_order_info['purchase_units'][0]['payments']['captures'][0];
    $amount_paid = $payment_capture['seller_receivable_breakdown']['gross_amount']['value'];
    $payment_id = $payment_capture['id'];

	// gets a comma-delimited string with the item names to use as "Order Name" in the receipt
    $item_names = [];
    foreach ($paypal_order_info['purchase_units'][0]['items'] as $item) {
        $item_names[] = $item['name'];
    }
    $item_names_str = implode(', ', $item_names);
    $item_names_str = rtrim($item_names_str,', ');

    // this part is the same regardless of order type... this is the start of the email body
    $email_body = "<div style=\"position: relative;max-width: 100vw;text-align:center;\">" .
        '<img src="cid:untrobotics-email-header">' .

        '	<div></div>' .

        '<div style="text-align: left; max-width: 500px; display: inline-block;">' .
        "	<p>Dear " . $payer['name']['given_name'] . ' ' . $payer['name']['surname'] . ",</p>";

    // sets the email subject and the thank you line for the email body based on order type
    if ($has_dues) {
        // has printful order
        if (isset($printful_order_info) && count($printful_order_info) > 0) {
            $printful_order_name = '';
            $items = '';
            foreach($printful_order_info as $o){
                $printful_order_name .= $o['order_name'];
                $items .= $o['order_name'] . ' - ' . $o['variant_name'] . ', ';
            }
            $items = rtrim($items, ', ');
            $subject = "Receipt for your UNT Robotics dues payment and purchase of {$printful_order_name}";
            $email_body .= "	<p>Thank you for paying your UNT Robotics dues and purchasing <strong>{$items}</strong> from our store. If you have not yet received the <em>Good Standing</em> role in the Discord server, please go to <a href=\"https://untro.bo/join/discord\">untro.bo/join/discord</a> to be automatically assigned the role. Please find a receipt for your payment below. A tracking number for your order will be e-mailed to you as soon as it is available.</p>";
        } else { // does not have printful order
            $subject = 'Receipt for your UNT Robotics dues payment';
            $email_body .= "	<p>Thank you for paying your UNT Robotics dues. If you have not yet received the <em>Good Standing</em> role in the Discord server, please go to <a href=\"https://untro.bo/join/discord\">untro.bo/join/discord</a> to be automatically assigned the role.</p>";
        }
    } else { // no dues, assume printful
        // throw error if no printful order info given
        if(!isset($printful_order_info)){
            throw new InvalidArgumentException('Unknown order item found when trying to construct receipt email!');
        }
        $printful_order_name = '';
        $items = '';
        foreach($printful_order_info as $o){
            $printful_order_name .= $o['order_name'];
            $items .= $o['order_name'] . ' - ' . $o['variant_name'] . ', ';
        }
        $items = rtrim($items, ', ');
        $subject = "Receipt for your purchase of {$printful_order_name}";
        $email_body .= "	<p>Thank you for your purchase of <strong>{$items}</strong> from our store. Please find a receipt for your payment below. A tracking number for your order will be e-mailed to you as soon as it is available.</p>";
    }

    // this part is the same regardless of order type
    // Adds the top part of the receipt, including PayPal payment ID, the date/time of the payment capture, amount paid, name of the payer, and the order name
    // Order name is a comma-delimited list of item names in the order
    $email_body .= '</div>' .

        '	<div></div>' .

        "	<div style=\"display: inline-block;padding: 15px;border: 1px solid #bdbdbd;border-radius: 10px;text-align: left;\">" .
        "		<h5 style=\"font-size: 12pt;margin: 0;font-weight: 600;\">🧾 Payment Receipt</h5>" .
        "		<ul>" .
        "			<li><strong>PayPal Payment ID</strong> {$payment_id}</li>" .
        "			<li><strong>Date/Time</strong> " . date('l jS \of F Y h:i:s A T', strtotime($payment_capture['create_time'])) . "</li>" .
        "			<li><strong>Payment Amount</strong> \${$amount_paid}</li>" .
        "			<li><strong>Name</strong> {$payer['name']['given_name']} {$payer['name']['surname']}</li>" .
        "			<li><strong>Order Name</strong> {$item_names_str}</li>";

    // add the "Semester" line to the receipt if dues are paid
    if ($has_dues) {
        $email_body .= "			<li><strong>Semester</strong> " . ($paypal_order_info['term_string']) . "</li>";
    }
    // add Product ID and Shipping Service to the receipt if there's a Printful order
    if (isset($printful_order_info) && count($printful_order_info) > 0) {


        // Numbers the products if there are multiple
        if(count($printful_order_info) > 1) {
            for ($i = 0; $i < count($printful_order_info); $i++) {
                $item_num = $i + 1;
                $email_body .= "			<li><strong>Product {$item_num}'s ID</strong> {$printful_order_info[$i]['product_id']}</li>" .
                    "			<li><strong>Product {$item_num}'s Shipping Service</strong> {$printful_order_info[$i]['shipping']}</li>";
            }
        } else{
            $email_body .= "			<li><strong>Product ID</strong> {$printful_order_info[0]['product_id']}</li>" .
                "			<li><strong>Shipping Service</strong> {$printful_order_info[0]['shipping']}</li>";
        }
    }

    // closing tags for the receipt section
    $email_body .= "		</ul>" .
        "	</div>" .
        "	<p></p>";

    // add a troubleshooting/help message based on order type
    if ($has_dues) {
        $email_body .= "	<p>If you need any assistance with your payment or with receiving the correct role, please reach out to <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a> or contact us <a href=\"" . DISCORD_INVITE_URL . "\">on Discord</a>.</p>";

    }
    if (isset($printful_order_info) && count($printful_order_info) > 0) {
        $email_body .= " <p>If you need any assistance with your merchandise order, please reach out to <a href=\"mailto:hello@untrobotics.com\">hello@untrobotics.com</a>.</p>";
    }

    // final closing tag, specifically closes the div from line 233
    $email_body .= "</div>";
	if($send_email) {
		// send email and return the send status
		return email(
			$payer['email_address'],
			$subject,
			$email_body,
			false,
			null,
			[
				[
					'content' => base64_encode(file_get_contents('../images/unt-robotics-email-header.jpg')),
					'type' => 'image/jpeg',
					'filename' => 'unt-robotics-email-header.jpg',
					'disposition' => 'inline',
					'content_id' => 'untrobotics-email-header'
				]
			]
		);
	}
	else{
		return ['to'=>$payer['email_address'],'subject'=>$subject,'body'=>$email_body];
	}

}