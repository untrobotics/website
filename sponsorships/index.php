<?php
require('../template/top.php');
head('Sponsorships', true);

// PayPal JS SDK client id for the active environment (sandbox flag set by auth()).
$paypal_client_id = $untrobotics->get_sandbox() ? PAYPAL_SANDBOX_CLIENT_ID : PAYPAL_CLIENT_ID;
$stripe_pk = STRIPE_PUBLISHABLE_KEY;
?>
<section class="section-50 section-md-75 section-lg-100">
    <div class="shell">
        <div class="range range-md-center">
            <div class="cell-md-10 cell-lg-8 cell-xl-6 text-left">

                <h1>Sponsorships</h1>
                <h6>Help us achieve our mission!</h6>
                <p>Your donation directly funds our robots, competitions, and workshops. Choose an amount below and give securely by card or PayPal.</p>

                <div class="offset-top-30" id="donation-widget">
                    <label class="form-label" style="display:block;margin-bottom:10px;font-weight:600;">Donation amount (USD)</label>
                    <div id="preset-amounts" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px;">
                        <button type="button" class="btn btn-default preset" data-amount="10">$10</button>
                        <button type="button" class="btn btn-default preset" data-amount="25">$25</button>
                        <button type="button" class="btn btn-default preset" data-amount="50">$50</button>
                        <button type="button" class="btn btn-default preset" data-amount="100">$100</button>
                    </div>
                    <div style="max-width:240px;margin-bottom:24px;position:relative;">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#888;">$</span>
                        <input type="number" id="donation-amount" min="1" max="10000" step="1" value="25"
                               class="form-control" style="padding-left:24px;" placeholder="Amount">
                    </div>

                    <div id="express-checkout-element" style="max-width:400px;margin-bottom:12px;"></div>
                    <div id="paypal-button-container" style="max-width:400px;"></div>
                    <div class="offset-top-10">
                        <button id="stripe-donate-button" type="button" class="btn btn-primary">Donate with Card</button>
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
            paymentMethods: { applePay: 'auto', googlePay: 'auto', link: 'auto', amazonPay: 'never', paypal: 'never', klarna: 'never' }
        });
        expressEl.mount('#express-checkout-element');
        expressEl.on('confirm', function () {
            var amt = getAmount();
            if (amt < 1) { showErr('Please enter a donation amount of at least $1.'); return; }
            showErr('');
            elements.submit().then(function (sub) {
                if (sub.error) { showErr(sub.error.message); return; }
                var p = new URLSearchParams(); p.set('source', 'donation'); p.set('amount', amt);
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
        var btn = this; btn.disabled = true; btn.textContent = 'Redirecting…';
        var p = new URLSearchParams(); p.set('source', 'donation'); p.set('amount', amt);
        fetch('/api/stripe/create-checkout-session', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: p.toString(), credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (d && d.url) { window.location = d.url; }
                else { showErr((d && d.error) || 'Unable to start checkout.'); btn.disabled = false; btn.textContent = 'Donate with Card'; }
            })
            .catch(function () { showErr('Unable to start checkout.'); btn.disabled = false; btn.textContent = 'Donate with Card'; });
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
