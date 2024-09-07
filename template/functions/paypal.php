<?php
// this file contains various helper functions

function printful_item(): PayPalItem {
    require_once(__DIR__ . "/../api/paypal/api-json-objects.php");
    require_once(__DIR__ . "/../config.php");

    $item = new PayPalItem();

    return $item;
}

function dues_item(): PayPalItem {
    require_once(__DIR__ . "/../api/paypal/api-json-objects.php");
    require_once(__DIR__ . "/../config.php");

    $item = new PayPalItem();

    return $item;
}

/**
 * @param string $text Text to display on the button
 * @param string[] $item_identifiers
 * @return void
 */
function get_payment_button(string $text, array $item_identifiers) {
    echo '<div class="paypal-button-container">
            <div class="paypal-button-overlay">
                <img src="/images/paypal-button.png" alt="">
                <script type="text/javascript">
                    async function get_order(){
                        let button = document.querySelector(".paypal-button-overlay > button")
                        //todo: change to loading symbol
                        
                        fetch(window.location.origin + "/paypal/get-order",{
                            method: "POST",
                            body: JSON.stringify({
                            item_identifiers: "[' . implode(',', $item_identifiers) . ']"
                            })
                        }).then(async (response)=>{
                           if(!response.ok){
                               button.textContent = "Error";
                               return;
                           } 
                           let json = await response.json();
                           button.onclick = ()=>{window.location.href = json.redirect_url}
                           window.location.href = json.redirect_url;
                        },()=>{
                            button.textContent = "Error"
                        })
                    }
                </script>
                <button type="submit" id="buy-product-now" class="btn btn-primary" onclick=get_order()>' . $text . '</button>
            </div>
        </div>';
}