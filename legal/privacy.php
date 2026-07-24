<?php
require("../template/top.php");
head("Privacy", true);
?>
<style>
    .legal-doc { max-width: 820px; }
    .legal-doc h1 { margin-bottom: 6px; }
    .legal-doc .updated { color: #888; font-size: 14px; margin-bottom: 30px; }
    .legal-doc h2 { margin-top: 40px; font-size: 22px; }
    .legal-doc h3 { margin-top: 24px; font-size: 17px; }
    .legal-doc p, .legal-doc li { line-height: 1.65; }
    .legal-doc ul { margin-bottom: 16px; }
</style>

<main class="page-content">
    <section class="section-50 section-md-75">
        <div class="shell">
            <div class="range range-md-center">
                <div class="cell-lg-10 legal-doc text-left">

                    <h1>Privacy Policy</h1>
                    <p class="updated">Last updated July 24, 2026</p>

                    <p>UNT Robotics ("we", "us", "our") is a student organization at the University of North Texas. This policy explains what information we collect through <strong>untrobotics.com</strong>, why we collect it, and who we share it with. If you have any questions, email us at <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>.</p>

                    <h2>Information we collect</h2>

                    <h3>Account information</h3>
                    <p>When you create an account we collect your name, email address, phone number, UNT EUID, expected graduation term and year, and your time zone. Your password is stored only as a secure one-way hash &mdash; we never store it in plain text.</p>

                    <h3>Payments (dues, merch, donations)</h3>
                    <p>Payments are processed by <strong>Stripe</strong> and <strong>PayPal</strong>. Your card details are entered directly with those providers &mdash; <strong>we never see or store your full card number</strong>. We keep a record of each transaction (amount, item, date, and a payment reference) so we can track dues, orders, and donations. For merchandise and dues t-shirts, we collect a shipping address and pass it to <strong>Printful</strong>, our print-on-demand fulfilment partner, to make and ship your order.</p>

                    <h3>Text messages (SMS)</h3>
                    <p>If you opt in to SMS, we use your phone number to send you updates and notifications through <strong>Twilio</strong>. SMS is entirely optional, and you can opt out at any time by replying <strong>STOP</strong>. See our <a href="/legal/sms-terms">SMS Terms</a> for details.</p>

                    <h3>Discord</h3>
                    <p>When you link your Discord account, we use Discord's OAuth to receive your Discord user ID. We use it only to assign and manage server roles (such as the Good Standing role for members with paid dues).</p>

                    <h3>Newsletter &amp; forms</h3>
                    <p>If you sign up for our newsletter, we store your email address (with us and with our email provider, <strong>Brevo</strong>) so we can send you updates. You can unsubscribe at any time using the link in any newsletter. When you use our contact, join, or event-registration forms, we collect the information you submit on them.</p>

                    <h3>Technical information</h3>
                    <p>Like most websites, we automatically receive your IP address and basic browser information, and we use a session cookie to keep you logged in. Public forms use <strong>Google reCAPTCHA</strong> to prevent spam and abuse, which is subject to Google's privacy policy and terms.</p>

                    <h2>How we use your information</h2>
                    <ul>
                        <li>Run our membership and dues program, and determine Good Standing.</li>
                        <li>Process payments and fulfil merchandise orders.</li>
                        <li>Send receipts, order tracking, event information, and (if you opt in) our newsletter and SMS updates.</li>
                        <li>Assign Discord roles and communicate with members.</li>
                        <li>Keep the site secure and prevent spam and abuse.</li>
                    </ul>

                    <h2>Who we share it with</h2>
                    <p>We do <strong>not</strong> sell your personal information. We share it only with the service providers that make the site work, and only as needed:</p>
                    <ul>
                        <li><strong>Stripe</strong> and <strong>PayPal</strong> &mdash; payment processing</li>
                        <li><strong>Printful</strong> &mdash; merchandise fulfilment (name and shipping address)</li>
                        <li><strong>Twilio</strong> &mdash; SMS delivery (phone number)</li>
                        <li><strong>Brevo</strong> &mdash; email and newsletter delivery (email address)</li>
                        <li><strong>Discord</strong> &mdash; account linking and roles (Discord ID)</li>
                        <li><strong>Google reCAPTCHA</strong> &mdash; spam prevention</li>
                    </ul>
                    <p>We may also disclose information if required by law or to protect the safety and rights of our members and the organization.</p>

                    <h2>Data retention &amp; security</h2>
                    <p>We keep your information for as long as your account is active or as needed to run the organization and meet our record-keeping needs. Passwords are hashed, the site is served over HTTPS, and access to member data is restricted to organization administrators.</p>

                    <h2>Your choices &amp; rights</h2>
                    <ul>
                        <li><strong>Access &amp; update:</strong> view and edit your account details any time on your <a href="/me/">profile</a>.</li>
                        <li><strong>Newsletter:</strong> unsubscribe using the link in any newsletter email.</li>
                        <li><strong>SMS:</strong> reply STOP to any message to opt out.</li>
                        <li><strong>Deletion:</strong> email <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a> to request deletion of your account and personal data.</li>
                    </ul>

                    <h2>Changes to this policy</h2>
                    <p>We may update this policy from time to time. When we do, we'll revise the "Last updated" date at the top of this page.</p>

                    <h2>Contact us</h2>
                    <p>Questions about this policy or your data? Email <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>.</p>

                </div>
            </div>
        </div>
    </section>
</main>

<?php
footer();
?>
