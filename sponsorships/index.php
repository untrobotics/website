<?php
require('../template/top.php');
head('Sponsorships', true);

// PayPal JS SDK client id for the active environment (sandbox flag set by auth()).
$paypal_client_id = $untrobotics->get_sandbox() ? PAYPAL_SANDBOX_CLIENT_ID : PAYPAL_CLIENT_ID;
$stripe_pk = STRIPE_PUBLISHABLE_KEY;
?>
<section class="breadcrumb-classic">
  <div class="rd-parallax">
    <div data-speed="0.25" data-type="media" data-url="/images/headers/sponsorships.jpg" class="rd-parallax-layer"></div>
    <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
      <div class="shell">
        <ul class="list-breadcrumb">
          <li><a href="/">Home</a></li>
          <li>Sponsorships</li>
        </ul>
      </div>
    </div>
  </div>
</section>
<section class="section-50 section-md-75 section-lg-100">
    <div class="shell">
        <div class="range range-md-center">
            <div class="cell-md-10 cell-lg-8 cell-xl-6 text-left">

                <h1>Sponsorships</h1>
                <h6>Help us achieve our mission!</h6>
                <p>Your donation directly funds our robots, competitions, and workshops. Choose an amount below and give securely by card or PayPal.</p>

                <div style="background:#eaf5ef; border:1px solid #d3e8dc; border-radius:10px; padding:18px 20px; margin:22px 0 8px; max-width:520px;">
                    <div style="font-weight:700; color:#166a3f; margin-bottom:4px;">Companies &amp; organizations</div>
                    <p style="font-size:14.5px; color:#3a403c; margin-bottom:12px;">See what your brand gets and how sponsoring works &mdash; recruiting access, brand visibility, and community impact.</p>
                    <a href="/sponsorships/flyer" class="btn btn-primary" style="margin-right:8px;">View sponsorship flyer</a>
                    <a href="/downloads/unt-robotics-sponsorship.pdf" target="_blank" rel="noopener" style="font-weight:600; color:#166a3f;">Download PDF &#8681;</a>
                </div>

                <style>
                    /* Keep every control on one column width so the wallet, PayPal
                       and card buttons line up. The theme's form-control fights the
                       number input, so the amount field is styled directly. */
                    #donation-widget { max-width: 400px; }
                    #donation-widget .field-label { display: block; margin-bottom: 10px; font-weight: 600; color: #1f1f1f; }
                    #preset-amounts { display: flex; gap: 8px; margin-bottom: 12px; }
                    #preset-amounts .preset { flex: 1 1 0; min-width: 0; padding-left: 0; padding-right: 0; }
                    .amount-field { display: flex; align-items: center; height: 52px; padding: 0 14px; margin-bottom: 20px; border: 1px solid #d5d8dc; border-radius: 4px; background: #fff; }
                    .amount-field .currency { font-size: 20px; color: #9aa0a6; margin-right: 6px; }
                    #donation-amount { flex: 1; width: 100%; border: 0; outline: 0; background: transparent; padding: 0; font-size: 22px; font-weight: 600; color: #1f1f1f; -moz-appearance: textfield; }
                    #donation-amount::-webkit-outer-spin-button,
                    #donation-amount::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
                    #stripe-donate-button { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 46px; border: 0; border-radius: 6px; background: #635bff; color: #fff; font-size: 15px; font-weight: 600; cursor: pointer; transition: background .15s; }
                    #stripe-donate-button:hover { background: #544dff; }
                    #stripe-donate-button:disabled { opacity: .6; cursor: default; }
                    .stripe-note { text-align: center; font-size: 11px; color: #9aa0a6; margin-top: 6px; }
                    #applepay-redirect { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; height: 46px; border: 0; border-radius: 6px; background: #000; color: #fff; font-size: 18px; font-weight: 500; cursor: pointer; }
                    /* Uniform spacing so every payment button (incl. PayPal's) is evenly stacked. */
                    #express-checkout-element, #applepay-redirect, #paypal-button-container, #stripe-donate-button { margin: 0 0 10px !important; }
                    #express-checkout-element:empty { margin: 0 !important; }
                    /* PayPal reserves ~8px of empty space below its buttons inside its own
                       container; trim its bottom margin so the gap below PayPal matches the
                       10px used between every other button. */
                    #paypal-button-container { min-height: 0; margin-bottom: 2px !important; }
                    .stripe-mark { display: inline-flex; align-items: center; padding: 3px 7px; border-radius: 4px; background: rgba(255,255,255,0.16); border: 1px solid rgba(255,255,255,0.32); }
    </style>
                <div class="offset-top-30" id="donation-widget">
                    <label class="field-label">Donation amount (USD)</label>
                    <div id="preset-amounts">
                        <button type="button" class="btn btn-default preset" data-amount="10">$10</button>
                        <button type="button" class="btn btn-default preset" data-amount="25">$25</button>
                        <button type="button" class="btn btn-default preset" data-amount="50">$50</button>
                        <button type="button" class="btn btn-default preset" data-amount="100">$100</button>
                    </div>
                    <div class="amount-field">
                        <span class="currency">$</span>
                        <input type="text" inputmode="decimal" id="donation-amount" value="25" placeholder="Amount" autocomplete="off">
                    </div>

                    <div id="express-checkout-element"></div>
                    <button id="applepay-redirect" type="button"><svg width="16" height="20" viewBox="0 0 384 512" fill="currentColor" aria-hidden="true"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C60.7 141.5 0 184.1 0 270c0 25.4 4.6 51.6 13.9 78.6 12.5 35.5 57.5 122.6 104.4 121.2 24.6-.6 42-17.4 74-17.4 31.1 0 47.3 17.4 74.7 17.4 47.4-.7 88-79.7 100-115.3-63.5-30-62.3-87.6-62.3-89.8zm-51.7-165c25-29.7 22.7-56.7 22-66.5-22.1 1.3-47.6 15-62.2 32.9-16 19.2-25.4 42.9-23.4 66 23.9 1.8 45.7-10.5 63.6-32.4z"/></svg> Pay</button>
                    <div id="paypal-button-container"></div>
                    <div>
                        <button id="stripe-donate-button" type="button">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            <span id="stripe-donate-label">Donate with card</span><svg class="stripe-mark" width="40" height="17" viewBox="0 0 468 222.5" fill="currentColor" aria-hidden="true"><path d="M414 113.4c0-25.6-12.4-45.8-36.1-45.8-23.8 0-38.2 20.2-38.2 45.6 0 30.1 17 45.3 41.4 45.3 11.9 0 20.9-2.7 27.7-6.5v-20c-6.8 3.4-14.6 5.5-24.5 5.5-9.7 0-18.3-3.4-19.4-15.2h48.9c0-1.3.2-6.5.2-8.9zm-49.4-9.5c0-11.3 6.9-16 13.2-16 6.1 0 12.6 4.7 12.6 16h-25.8zM301.1 67.6c-9.8 0-16.1 4.6-19.6 7.8l-1.3-6.2h-22v116.6l25-5.3.1-28.3c3.6 2.6 8.9 6.3 17.7 6.3 17.9 0 34.2-14.4 34.2-46.1-.1-29-16.6-44.8-34.1-44.8zm-6 68.9c-5.9 0-9.4-2.1-11.8-4.7l-.1-37.1c2.6-2.9 6.2-4.9 11.9-4.9 9.1 0 15.4 10.2 15.4 23.3 0 13.4-6.2 23.4-15.4 23.4zM223.8 61.7l25.1-5.4V36l-25.1 5.3v20.4zM223.8 69.3h25.1v87.5h-25.1zM196.9 76.7l-1.6-7.4h-21.6v87.5h25V97.8c5.9-7.7 15.9-6.3 19-5.2v-23c-3.2-1.2-14.9-3.4-20.8 7.1zM146.9 47.6l-24.4 5.2-.1 80.1c0 14.8 11.1 25.7 25.9 25.7 8.2 0 14.2-1.5 17.5-3.3V135c-3.2 1.3-19 5.9-19-8.9V90.6h19V69.3h-19l.1-21.7zM79.3 94.7c0-3.9 3.2-5.4 8.5-5.4 7.6 0 17.2 2.3 24.8 6.4V72.2c-8.3-3.3-16.5-4.6-24.8-4.6C67.5 67.6 54 78.2 54 95.9c0 27.6 38 23.2 38 35.1 0 4.6-4 6.1-9.6 6.1-8.3 0-18.9-3.4-27.3-8v23.8c9.3 4 18.7 5.7 27.3 5.7 20.8 0 35.1-10.3 35.1-28.2-.1-29.8-38.2-24.5-38.2-35.7z"/></svg>
                        </button>
                    </div>
                    <div id="donation-error" class="text-danger offset-top-10" style="display:none;"></div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php footer(false); ?>
<script src="https://js.stripe.com/v3/"></script>
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo htmlspecialchars($paypal_client_id); ?>&currency=USD&disable-funding=card"></script>
<script>
(function () {
    var amtInput = document.getElementById('donation-amount');
    function getAmount() { var v = parseFloat(amtInput.value); return (isNaN(v) || v < 1) ? 0 : Math.round(v * 100) / 100; }
    function showErr(m) { var e = document.getElementById('donation-error'); e.style.display = m ? 'block' : 'none'; e.textContent = m || ''; }

    // Express Checkout Element: native Apple Pay / Google Pay / Link, inline.
    var stripe = window.Stripe ? Stripe('<?php echo htmlspecialchars($stripe_pk, ENT_QUOTES); ?>') : null;
    var elements = null;
    function currentCents() { var a = getAmount(); return a >= 1 ? Math.round(a * 100) : 0; }
    function syncExpressAmount() { if (elements && currentCents() >= 100) { elements.update({ amount: currentCents() }); } }
    if (stripe) {
        elements = stripe.elements({ mode: 'payment', amount: currentCents() || 2500, currency: 'usd' });
        var expressEl = elements.create('expressCheckout', {
            emailRequired: true,
            paymentMethods: { applePay: 'auto', googlePay: 'auto', link: 'never', amazonPay: 'never', paypal: 'never', klarna: 'never' }
        });
        expressEl.mount('#express-checkout-element');
        var apRedirect = document.getElementById('applepay-redirect');
        expressEl.on('ready', function (e) {
            var avail = e && e.availablePaymentMethods;
            if (apRedirect && avail && avail.applePay) { apRedirect.style.display = 'none'; }
            // No wallet on this device: ECE renders an empty sliver — hide it so it
            // doesn't leave a stray gap above the fallback buttons.
            if (!avail || (!avail.applePay && !avail.googlePay)) {
                var eceEl = document.getElementById('express-checkout-element');
                if (eceEl) { eceEl.style.display = 'none'; }
            }
        });
        if (apRedirect) {
            apRedirect.addEventListener('click', function () {
                var amt = getAmount();
                if (amt < 1) { showErr('Please enter a donation amount of at least $1.'); return; }
                apRedirect.disabled = true;
                var rp = new URLSearchParams(); rp.set('source', 'donation'); rp.set('amount', amt);
                fetch('/api/stripe/create-checkout-session', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: rp.toString(), credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { if (d && d.url) { window.location = d.url; } else { apRedirect.disabled = false; showErr((d && d.error) || 'Unable to start checkout.'); } })
                    .catch(function () { apRedirect.disabled = false; showErr('Unable to start checkout.'); });
            });
        }
        expressEl.on('confirm', function (event) {
            var amt = getAmount();
            if (amt < 1) { showErr('Please enter a donation amount of at least $1.'); return; }
            showErr('');
            elements.submit().then(function (sub) {
                if (sub.error) { showErr(sub.error.message); return; }
                var p = new URLSearchParams(); p.set('source', 'donation'); p.set('amount', amt);
                p.set('email', (event.billingDetails && event.billingDetails.email) || '');
                return fetch('/api/stripe/create-payment-intent.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: p.toString(), credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (!d || !d.clientSecret) { showErr((d && d.error) || 'Unable to start payment.'); return; }
                        return stripe.confirmPayment({ elements: elements, clientSecret: d.clientSecret, confirmParams: { return_url: location.origin + '/sponsorships/donate/thank-you' } })
                            .then(function (res) { if (res.error) { showErr(res.error.message); } });
                    });
            }).catch(function () { showErr('Unable to start payment.'); });
        });
    }
    amtInput.addEventListener('input', syncExpressAmount);

    document.querySelectorAll('#preset-amounts .preset').forEach(function (b) {
        b.addEventListener('click', function () { amtInput.value = b.getAttribute('data-amount'); showErr(''); syncExpressAmount(); });
    });

    // Stripe Checkout (Card).
    document.getElementById('stripe-donate-button').addEventListener('click', function () {
        var amt = getAmount();
        if (amt < 1) { showErr('Please enter a donation amount of at least $1.'); return; }
        showErr('');
        var btn = this; var lbl = document.getElementById('stripe-donate-label'); btn.disabled = true; lbl.textContent = 'Redirecting…';
        var p = new URLSearchParams(); p.set('source', 'donation'); p.set('amount', amt);
        fetch('/api/stripe/create-checkout-session', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: p.toString(), credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.url) { window.location = d.url; }
                else { showErr((d && d.error) || 'Unable to start checkout.'); btn.disabled = false; lbl.textContent = 'Donate with card'; }
            })
            .catch(function () { showErr('Unable to start checkout.'); btn.disabled = false; lbl.textContent = 'Donate with card'; });
    });

    // PayPal Smart Buttons.
    if (window.paypal) {
        paypal.Buttons({
            createOrder: function () {
                var amt = getAmount();
                if (amt < 1) { showErr('Please enter a donation amount of at least $1.'); return Promise.reject(new Error('invalid amount')); }
                showErr('');
                var p = new URLSearchParams(); p.set('source', 'donation'); p.set('amount', amt);
                return fetch('/api/paypal/orders/create.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: p.toString(), credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { if (!d || !d.id) { throw new Error((d && d.error) || 'Unable to start checkout.'); } return d.id; });
            },
            onApprove: function (data) {
                return fetch('/api/paypal/orders/capture.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: data.orderID }), credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (d) { if (d && d.success) { window.location = '/sponsorships/donate/thank-you'; } else { showErr((d && d.error) || 'Your payment could not be completed.'); } });
            },
            onError: function () { showErr('An error occurred with PayPal. Please try again.'); }
        }).render('#paypal-button-container');
    }
})();
</script>
