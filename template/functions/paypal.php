<?php
// this file contains various helper functions

/**
 * Gets a payment button whose items change due to DOM manipulations. For orders that our based only on the page, see {@see get_payment_button_constant()}
 * @param string $text Text to display on the button
 * @param string[] $dom_selectors Selectors with the item identifiers to add to the order. Item identifiers should be stored in the 'item' attribute
 * @param string $return_uri URI to return the user to after order approval
 * @param string $cancel_uri URI to return the user to after order cancellation
 */
function get_payment_button(string $text, array $dom_selectors, string $return_uri, string $cancel_uri) {
    echo '<div class="paypal-button-container">
            <div class="paypal-button-overlay">
                <img src="/images/paypal-button.png" alt="">
                <button type="submit" id="buy-product-now" class="btn btn-primary">' . $text . '</button>
                <script type="text/javascript">
                    let order_id = new URLSearchParams(window.location.search).get("token");
                    /*if(order_id === null || order_id === "") {
                        document.querySelector("button#buy-product-now").addEventListener("click", get_order)    
                    } else{
                        document.querySelector("button#buy-product-now").addEventListener("click", get_order_by_id)
                    }*/
                    document.querySelector("button#buy-product-now").addEventListener("click", get_order)
                    async function get_order(){
                        let button = document.querySelector(".paypal-button-overlay > button")
                        button.removeEventListener("click", get_order)
                        button.setAttribute("disabled", "")
                        button.textContent = "Loading..."
                        console.log("calling post");
                        let selectors = ' . json_encode($dom_selectors) . '
                        let items = []
                        let variants = []
                        selectors.forEach(selector => {
                            let el = document.querySelector(selector)
                            let item = el.getAttribute("item")
                            if(item !== null && item !== ""){
                                items.push(item)
                                let variant = el.getAttribute("variant")
                                variants.push(variant) 
                            }
                        })
                        if(items.length === 0)
                            {
                            button.addEventListener("click", get_order)
                            button.removeAttribute("disabled")
                            console.log(selectors)
                            return
                            }
                        fetch(window.location.origin + "/paypal/get-order",{
                            method: "POST",
                            credentials: "include",
                            body: JSON.stringify({
                            item_identifiers: items,
                            variant_identifiers: variants,
                            return_url: "https://' . "{$_SERVER['SERVER_NAME']}/{$return_uri}" . '",
                            cancel_url: "https://' . "{$_SERVER['SERVER_NAME']}/{$cancel_uri}" . '"
                            })
                        }).then(async (response)=>{
                           button.removeAttribute("disabled")
                           if(!response.ok){
                               button.textContent = "Error try again";
                               button.addEventListener("click", get_order)
                               return;
                           } 
                           let json = await response.json();
                           button.onclick = ()=>{window.location.href = json.redirect_url}
                           window.location.href = json.redirect_url;
                        },()=>{
                            button.removeAttribute("disabled")
                            button.textContent = "Error try again"
                            button.addEventListener("click", get_order)
                        })
                    }
                    /*async function get_order_by_id(){
                        let button = document.querySelector(".paypal-button-overlay > button")
                        button.removeEventListener("click", get_order_by_id)
                        button.textContent = "Loading..."
                        console.log("calling post");
                        let order_id = new URLSearchParams(window.location.search).get("token");
                        fetch(window.location.origin + "/paypal/get-order",{
                            method: "POST",
                            body: JSON.stringify({
                            id: order_id,
                            return_url: "' . "{$_SERVER['SERVER_NAME']}/{$return_uri}" . '",
                            cancel_url: "' . "{$_SERVER['SERVER_NAME']}/{$cancel_uri}" . '"
                            })
                        }).then(async (response)=>{
                           if(!response.ok){
                               if(response.status === 404){
                                    button.textContent = "Error. Try Again"       
                               }
                               button.textContent = "Error";
                               return;
                           } 
                           let json = await response.json();
                           button.onclick = ()=>{window.location.href = json.redirect_url}
                           window.location.href = json.redirect_url;
                        },()=>{
                            button.textContent = "Error. Try Again"
                            
                        })
                    }*/
                </script>
            </div>
        </div>';
}

/**
 * Gets a payment button whose items don't change. For orders that may vary based on an input field, see {@see get_payment_button()}
 * @param string $text Text to display on the button
 * @param string[] $item_names An array of the item names. Indices should match with that of $variant_names
 * @param array $variant_names An array of the item variants' names. Indices should match with that of $item_names. Use null or an empty string if there is no variant info
 * @param string $return_uri URI to return the user to after order approval
 * @param string $cancel_uri URI to return the user to after order cancellation
 * @return void
 */
function get_payment_button_constant(string $text, array $item_names, array $variant_names, string $return_uri, string $cancel_uri) {
    echo '<div class="paypal-button-container">
            <div class="paypal-button-overlay">
                <img src="/images/paypal-button.png" alt="">
                <button type="submit" id="buy-product-now" class="btn btn-primary">' . $text . '</button>
                <script type="text/javascript">
                    let order_id = new URLSearchParams(window.location.search).get("token");
                    document.querySelector("button#buy-product-now").addEventListener("click", get_order)
                    async function get_order(){
                        let button = document.querySelector(".paypal-button-overlay > button")
                        button.removeEventListener("click", get_order)
                        button.setAttribute("disabled", "")
                        button.textContent = "Loading..."
                        console.log("calling post");
                        
                        fetch(window.location.origin + "/paypal/get-order",{
                            method: "POST",
                            credentials: "include",
                            body: JSON.stringify({
                            item_identifiers: ' . json_encode($item_names) . ',
                            variant_identifiers: ' . json_encode($variant_names) . ',
                            return_url: "https://' . "{$_SERVER['SERVER_NAME']}/{$return_uri}" . '",
                            cancel_url: "https://' . "{$_SERVER['SERVER_NAME']}/{$cancel_uri}" . '"
                            })
                        }).then(async (response)=>{
                           button.removeAttribute("disabled")
                           if(!response.ok){
                               button.textContent = "Error try again";
                               button.addEventListener("click", get_order)
                               return;
                           } 
                           let json = await response.json();
                           button.onclick = ()=>{window.location.href = json.redirect_url}
                           window.location.href = json.redirect_url;
                        },()=>{
                            button.removeAttribute("disabled")
                            button.textContent = "Error try again"
                            button.addEventListener("click", get_order)
                        })
                    }
                    /*async function get_order_by_id(){
                        let button = document.querySelector(".paypal-button-overlay > button")
                        button.removeEventListener("click", get_order_by_id)
                        button.textContent = "Loading..."
                        console.log("calling post");
                        let order_id = new URLSearchParams(window.location.search).get("token");
                        fetch(window.location.origin + "/paypal/get-order",{
                            method: "POST",
                            body: JSON.stringify({
                            id: order_id,
                            return_url: "' . "{$_SERVER['SERVER_NAME']}/{$return_uri}" . '",
                            cancel_url: "' . "{$_SERVER['SERVER_NAME']}/{$cancel_uri}" . '"
                            })
                        }).then(async (response)=>{
                           if(!response.ok){
                               if(response.status === 404){
                                    button.textContent = "Error. Try Again"       
                               }
                               button.textContent = "Error";
                               return;
                           } 
                           let json = await response.json();
                           button.onclick = ()=>{window.location.href = json.redirect_url}
                           window.location.href = json.redirect_url;
                        },()=>{
                            button.textContent = "Error. Try Again"
                            
                        })
                    }*/
                </script>
            </div>
        </div>';
}

/**
 * Gets an order's status from our database. Specific table: paypal_orders
 * @param string $order_id PayPal order ID
 * @return false|null|string The order status. Null if a database error occurred. False if no order with that ID could be found.
 */
function get_order_status_internal(string $order_id): ?string {
    require_once(__DIR__ . '/../top.php');
    global $db;
    $q = $db->query("SELECT status FROM paypal_orders WHERE paypal_order_id='{$db->real_escape_string($order_id)}'");
    if (!$q) {
        return null;
    }
    if ($q->num_rows < 0) {
        return false;
    }
    return $q->fetch_assoc()['status'];
}

/**
 * Emails a receipt for a PayPal order
 * @param $paypal_order_info array The JSON-decoded response for get_order PayPal API call. This should be an associative array
 * @param $printful_order_info array[] An array containing information about the Printful order. Each index represents one Printful order. The required information is "variant_id" and "shipping_service".
 * @param $has_dues bool A boolean representing whether this order has dues or not. Defaults to false
 * @return bool True if the email received an HTTP success code. False otherwise. See {@see email()} for more information
 */
function email_receipt(array &$paypal_order_info, ?array $printful_order_info, bool $has_dues = false): bool {
    $payer = $paypal_order_info['payer'];
    $payment_capture = $paypal_order_info['purchase_units'][0]['payments']['captures'][0];
    $amount_paid = $payment_capture['seller_receivable_breakdown']['gross_amount']['value'];
    $payment_id = $payment_capture['id'];
    // this is set in the dues payment handler
    $term_string = $paypal_order_info['term_string'];

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
            $subject = "Receipt for your UNT Robotics dues payment and purchase of {$printful_order_info['order_name']}";
            $email_body .= "	<p>Thank you for paying your UNT Robotics dues and <strong>{$printful_order_info['order_name']} - {$printful_order_info['order_variant_name']}</strong> from our store. If you have not yet received the <em>Good Standing</em> role in the Discord server, please go to <a href=\"https://untro.bo/join/discord\">untro.bo/join/discord</a> to be automatically assigned the role. Please find a receipt for your payment below. A tracking number for your order will be e-mailed to you as soon as it is available.</p>";
        } else { // does not have printful order
            $subject = 'Receipt for your UNT Robotics dues payment';
            $email_body .= "	<p>Thank you for paying your UNT Robotics dues. If you have not yet received the <em>Good Standing</em> role in the Discord server, please go to <a href=\"https://untro.bo/join/discord\">untro.bo/join/discord</a> to be automatically assigned the role.</p>";
        }
    } else { // no dues, assume printful
        // throw error if no printful order info given
        if(!isset($printful_order_info)){
            throw new InvalidArgumentException('Unknown order item found when trying to construct receipt email!');
        }
        $subject = "Receipt for your purchase of {$printful_order_info['order_name']}";
        $email_body .= "	<p>Thank you for your purchase of <strong>{$printful_order_info['order_name']} - {$printful_order_info['order_variant_name']}</strong> from our store. Please find a receipt for your payment below. A tracking number for your order will be e-mailed to you as soon as it is available.</p>";
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
        $email_body .= "			<li><strong>Semester</strong> {$term_string}</li>";
    }
    // add Product ID and Shipping Service to the receipt if there's a Printful order
    if (isset($printful_order_info) && count($printful_order_info) > 0) {
        //todo: create the printful_order_info array in handlers/printful

        // Numbers the products if there are multiple
        if(count($printful_order_info) > 1) {
            for ($i = 0; $i < count($printful_order_info); $i++) {
                $item_num = $i + 1;
                $email_body .= "			<li><strong>Product {$item_num}'s ID</strong> {$printful_order_info['variant_id']}</li>" .
                    "			<li><strong>Product {$item_num}'s Shipping Service</strong> {$printful_order_info['shipping_service']}</li>";
            }
        } else{
            $email_body .= "			<li><strong>Product ID</strong> {$printful_order_info['variant_id']}</li>" .
                "			<li><strong>Shipping Service</strong> {$printful_order_info['shipping_service']}</li>";
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