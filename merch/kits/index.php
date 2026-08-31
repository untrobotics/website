<?php
require('../../template/top.php');
head('Electronics Kit Preorder', true);

// PayPal JS SDK client id for the active environment.
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
          <li><a href="/merch">Merch</a></li>
          <li>Electronics Kit</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<section class="section-50 section-md-75 section-lg-100">
  <div class="shell">
    <style>
      .kit-layout { display:grid; grid-template-columns:minmax(0,1fr) 400px; gap:52px; align-items:start; max-width:980px; margin:0 auto; }
      .kit-left h1 { font-size:40px; }
      .kit-left img { border-radius:10px; margin:0 0 20px; max-width:100%; height:auto; }
      .kit-left ul { list-style:none; padding:0; }
      .kit-left ul li { padding:5px 0 5px 24px; position:relative; }
      .kit-left ul li:before { content:"\2713"; position:absolute; left:0; color:#22a45d; font-weight:700; }
      .kit-widget { background:#fff; border:1px solid #e3e6e4; border-radius:12px; padding:24px; box-shadow:0 2px 10px rgba(0,0,0,.04); }
      .kit-widget label { display:block; font-weight:600; font-size:13px; color:#3a403c; margin:12px 0 5px; }
      .kit-widget input { width:100%; height:44px; padding:0 12px; border:1px solid #cacaca; border-radius:6px; font-size:15px; }
      .kit-price { display:flex; align-items:baseline; gap:8px; margin:20px 0 4px; }
      .kit-price .amt { font-size:34px; font-weight:800; color:#166a3f; }
      .kit-price .lbl { color:#777; font-size:13px; }
      /* Flex gap gives every button the same rhythm regardless of which SDK
         rendered it (the Stripe wallet iframe brings its own margins), so zero
         each child's own margins — they'd add to the gap and read as uneven. */
      .pay-buttons { display:flex; flex-direction:column; gap:10px; }
      .pay-buttons > * { margin:0 !important; }
      .pay-buttons #express-checkout-element:empty { display:none; }
      /* PayPal reserves ~8px of empty space below its buttons; pull the note up
         so the visible gap under PayPal matches the 10px used everywhere else. */
      .pay-buttons #paypal-button-container { min-height:0; margin-bottom:-8px !important; }
      #stripe-kit-button { display:flex; align-items:center; justify-content:center; gap:8px; width:100%; height:46px; border:0; border-radius:6px; background:#635bff; color:#fff; font-size:15px; font-weight:600; cursor:pointer; transition:background .15s; }
      #stripe-kit-button:hover { background:#544dff; }
      #stripe-kit-button:disabled { opacity:.6; cursor:default; }
      #applepay-redirect { display:none; align-items:center; justify-content:center; gap:6px; width:100%; height:46px; border:0; border-radius:6px; background:#000; color:#fff; font-size:17px; font-weight:500; cursor:pointer; }
      #applepay-redirect:disabled { opacity:.6; cursor:default; }
      #kit-error { display:none; margin:12px 0 0; padding:10px 12px; border-radius:6px; background:#fde2e1; color:#a12622; font-size:13px; }
      .kit-note { font-size:12px; color:#8a8f8c; margin-top:12px; line-height:1.5; }
      @media (max-width:900px){ .kit-layout { grid-template-columns:1fr; max-width:440px; gap:26px; } }
    </style>

    <div class="kit-layout">
      <div class="kit-left text-left">
        <h1>Electronics Kit</h1>
        <h6>Everything you need for this semester&rsquo;s build workshops</h6>
        <p>Your own box of parts to build along at our workshops all semester &mdash; from a light-sensing LED nightlight to sound-reactive motors and an RFID door lock. A hands-on intro to Arduino, circuits, sensors, and code. Preorder below; we&rsquo;ll put your kit together and email you the moment it&rsquo;s ready to pick up at a general meeting.</p>
        <p><strong>Each kit includes:</strong></p>
        <ul>
          <li>Arduino Uno (USB-C) + breadboard</li>
          <li>Jumper wires (M&ndash;M and M&ndash;F) &amp; assorted resistors</li>
          <li>LEDs (red, green, blue, yellow, orange), buttons &amp; switches</li>
          <li>Potentiometer + buzzer</li>
          <li>Sensors: soil-moisture, light, touch &amp; sound</li>
          <li>RFID reader + tag</li>
          <li>SG90 servo + 3V DC motor</li>
          <li>Buck converter</li>
          <li>8-bit RGB LED ring + 8&times;8 RGB LED matrix</li>
          <li>I&sup2;C LCD display</li>
          <li>Divided storage box to keep it all organized</li>
        </ul>
      </div>

      <div class="kit-right">
        <div class="kit-widget">
          <div class="kit-price"><span class="amt">$40.00</span><span class="lbl">per kit</span></div>
          <label for="kit-first">First name</label>
          <input type="text" id="kit-first" autocomplete="given-name" maxlength="100">
          <label for="kit-last">Last name</label>
          <input type="text" id="kit-last" autocomplete="family-name" maxlength="100">
          <label for="kit-phone">Phone number</label>
          <input type="tel" id="kit-phone" autocomplete="tel" maxlength="32" placeholder="(817) 555-1234">
          <label for="kit-email">Email <span style="font-weight:400;color:#9aa0a6;">(optional)</span></label>
          <input type="email" id="kit-email" autocomplete="email" maxlength="255">

          <div class="pay-buttons" style="margin-top:20px;">
            <button id="stripe-kit-button" type="button">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              <span>Pay with card</span>
            </button>
            <div id="express-checkout-element"></div>
            <button id="applepay-redirect" type="button"><svg width="16" height="20" viewBox="0 0 384 512" fill="currentColor" aria-hidden="true"><path d="M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C60.7 141.5 0 184.1 0 270c0 25.4 4.6 51.6 13.9 78.6 12.5 35.5 57.5 122.6 104.4 121.2 24.6-.6 42-17.4 74-17.4 31.1 0 47.3 17.4 74.7 17.4 47.4-.7 88-79.7 100-115.3-63.5-30-62.3-87.6-62.3-89.8zm-51.7-165c25-29.7 22.7-56.7 22-66.5-22.1 1.3-47.6 15-62.2 32.9-16 19.2-25.4 42.9-23.4 66 23.9 1.8 45.7-10.5 63.6-32.4z"/></svg> Pay</button>
            <div id="paypal-button-container"></div>
          </div>
          <div id="kit-error" class="text-danger offset-top-10" style="display:none;"></div>
          <p class="kit-note">1 kit per person. Kits are picked up in person at our general meetings &mdash; see the <a href="/events">event calendar</a> and <a href="/join/discord">Discord</a> for times.</p>
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
  function val(id) { return (document.getElementById(id).value || '').trim(); }
  function fields() { return { first_name: val('kit-first'), last_name: val('kit-last'), phone: val('kit-phone'), email: val('kit-email') }; }
  function validate() {
    var f = fields();
    if (!f.first_name || !f.last_name) return 'Please enter your first and last name.';
    if (f.phone.replace(/\D/g, '').length < 10) return 'Please enter a valid phone number.';
    if (f.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.email)) return 'Please enter a valid email, or leave it blank.';
    return null;
  }
  function showErr(m) { var e = document.getElementById('kit-error'); e.style.display = m ? 'block' : 'none'; e.textContent = m || ''; }
  function params(extraEmail) {
    var f = fields();
    var p = new URLSearchParams();
    p.set('source', 'kit');
    p.set('first_name', f.first_name);
    p.set('last_name', f.last_name);
    p.set('phone', f.phone);
    p.set('email', f.email || extraEmail || '');
    return p;
  }

  // Stripe Checkout (hosted — card + Apple Pay).
  document.getElementById('stripe-kit-button').addEventListener('click', function () {
    var err = validate(); if (err) { showErr(err); return; }
    showErr('');
    var btn = this, lbl = btn.querySelector('span'), old = lbl ? lbl.textContent : '';
    btn.disabled = true; if (lbl) lbl.textContent = 'Redirecting…';
    fetch('/api/stripe/create-checkout-session', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params().toString(), credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d && d.url) { window.location = d.url; }
        else { showErr((d && d.error) || 'Unable to start checkout.'); btn.disabled = false; if (lbl) lbl.textContent = old; }
      })
      .catch(function () { showErr('Unable to start checkout.'); btn.disabled = false; if (lbl) lbl.textContent = old; });
  });

  // Inline wallet — Apple Pay / Google Pay via the Express Checkout Element.
  var pk = '<?php echo htmlspecialchars($stripe_pk, ENT_QUOTES); ?>';
  if (window.Stripe && pk) {
    var stripe = Stripe(pk);
    var elements = stripe.elements({ mode: 'payment', amount: 4000, currency: 'usd' });
    var expressEl = elements.create('expressCheckout', {
      emailRequired: true,
      paymentMethods: { applePay: 'auto', googlePay: 'auto', link: 'never', amazonPay: 'never', paypal: 'never', klarna: 'never' }
    });
    expressEl.mount('#express-checkout-element');
    var apRedirect = document.getElementById('applepay-redirect');
    expressEl.on('ready', function (e) {
      var avail = e && e.availablePaymentMethods;
      // If no inline wallet is available, offer the hosted-checkout Apple Pay fallback button.
      if (apRedirect && (!avail || (!avail.applePay && !avail.googlePay))) { apRedirect.style.display = 'flex'; }
    });
    // Validate the form BEFORE the wallet sheet opens; refusing to resolve keeps it closed.
    expressEl.on('click', function (event) {
      var err = validate(); if (err) { showErr(err); return; }
      showErr('');
      event.resolve();
    });
    expressEl.on('confirm', function (event) {
      var err = validate(); if (err) { showErr(err); return; }
      showErr('');
      elements.submit().then(function (sub) {
        if (sub && sub.error) { showErr(sub.error.message); return; }
        var walletEmail = (event && event.billingDetails && event.billingDetails.email) || '';
        return fetch('/api/stripe/create-payment-intent.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params(walletEmail).toString(), credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            if (!d || !d.clientSecret) { showErr((d && d.error) || 'Unable to start payment.'); return; }
            return stripe.confirmPayment({ elements: elements, clientSecret: d.clientSecret, confirmParams: { return_url: location.origin + '/merch/kits/thank-you' } })
              .then(function (res) { if (res && res.error) { showErr(res.error.message); } });
          });
      }).catch(function () { showErr('Unable to start payment.'); });
    });
    // Fallback button (only shown when no inline wallet) → hosted checkout, which offers Apple Pay itself.
    if (apRedirect) {
      apRedirect.addEventListener('click', function () {
        var err = validate(); if (err) { showErr(err); return; }
        showErr(''); apRedirect.disabled = true;
        fetch('/api/stripe/create-checkout-session', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params().toString(), credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (d && d.url) { window.location = d.url; } else { apRedirect.disabled = false; showErr((d && d.error) || 'Unable to start checkout.'); } })
          .catch(function () { apRedirect.disabled = false; showErr('Unable to start checkout.'); });
      });
    }
  }

  // PayPal Smart Buttons.
  if (window.paypal) {
    paypal.Buttons({
      createOrder: function () {
        var err = validate(); if (err) { showErr(err); return Promise.reject(new Error('invalid')); }
        showErr('');
        return fetch('/api/paypal/orders/create.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params().toString(), credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (!d || !d.id) { throw new Error((d && d.error) || 'Unable to start checkout.'); } return d.id; });
      },
      onApprove: function (data) {
        return fetch('/api/paypal/orders/capture.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ order_id: data.orderID }), credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (d) { if (d && d.success) { window.location = '/merch/kits/thank-you'; } else { showErr((d && d.error) || 'Your payment could not be completed.'); } });
      },
      onError: function () { showErr('An error occurred with PayPal. Please try again.'); }
    }).render('#paypal-button-container');
  }
})();
</script>
