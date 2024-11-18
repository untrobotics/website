<?php
require('../template/top.php');
require_once(BASE . '/template/functions/payment_button.php');
head('Sponsorships', true, false, false, "Help us achieve our mission!");

$payment_button = new PaymentButton(
    'UNT Robotics General Sponsorship or Donation',
    null,
    'Donate Now'
);
$payment_button->set_complete_return_uri('/sponsorships/donate/thank-you');
?>
<section class="section-50 section-md-75 section-lg-100">
    <div class="shell range-offset-1">
        <div class="range">
            <div class="cell-lg-6">

                <h1>Sponsorships</h1>
                <h6>Help us achieve our mission!</h6>

            </div>
        </div>
        <div class="cell-lg-12 offset-lg-top-50">
            <?php echo $payment_button->get_button()->button; ?>
        </div>
    </div>
</section>

<?php
footer();
?>
