<?php
// this file contains various helper functions

/**
 * @param string $text Text to display on the button
 * @param string[] $dom_selectors Selectors with the item identifiers to add to the order. Item identifiers should be stored in the 'item' attribute
 * @param string $return_uri URI to return the user to after order approval
 * @param string $cancel_uri URI to return the user to after order cancellation
 * @return void
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
                        selectors.forEach(selector => {
                            let item = document.querySelector(selector).getAttribute("item");
                            if(item !== null && item !== "")
                                items.push(item)
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
                            return_url: "https://' ."{$_SERVER['SERVER_NAME']}/{$return_uri}" . '",
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
                            return_url: "' ."{$_SERVER['SERVER_NAME']}/{$return_uri}" . '",
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