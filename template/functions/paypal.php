<?php
// this file contains various helper functions

/**
 * @param string $text Text to display on the button
 * @param string[] $item_identifiers
 * @return void
 */
function get_payment_button(string $text, array $item_identifiers, string $return_url, string $cancel_url) {
    echo '<div class="paypal-button-container">
            <div class="paypal-button-overlay">
                <img src="/images/paypal-button.png" alt="">
                <button type="submit" id="buy-product-now" class="btn btn-primary">' . $text . '</button>
                <script type="text/javascript">
                    document.querySelector("button#buy-product-now").addEventListener("click", get_order)
                    async function get_order(){
                        let button = document.querySelector(".paypal-button-overlay > button")
                        //todo: change to loading symbol
                        console.log("calling post");
                        fetch(window.location.origin + "/paypal/get-order",{
                            method: "POST",
                            body: JSON.stringify({
                            item_identifiers: [' . implode(',', $item_identifiers) . '],
                            return_url: "' . $return_url . '",
                            cancel_url: "' . $cancel_url . '"
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
            </div>
        </div>';
}