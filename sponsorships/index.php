<?php
require('../template/top.php');
//todo:
require_once('../template/functions/paypal.php');
head('Sponsorships', true);
?>
<style>
    .stepper{
        max-width: none;
        width: auto;
    }
</style>
<section class="section-50 section-md-75 section-lg-100">
    <div class="shell range-offset-1">
        <div class="range">
            <div class="cell-lg-6">

                <h1>Sponsorships</h1>
                <h6>Help us achieve our mission!</h6>

            </div>
        </div>
        <div class="cell-lg-12 offset-lg-top-50">

            <?php
            get_payment_button('Donate', [['type'=>'donation']], 'sponsorships/donate/thank-you', 'sponsorships',
            ' <div class="form-group">
                        <label for="donate-amount" class="form-label" style="margin-left:20px">Amount (USD)</label>
                        <input id="donate-amount" type="number" min="0.01" step="any" name="items[0][' . 'amount' . ']" data-constraints="@Required" class="form-control" style="width:300px; max-width:300px" required>
                    </div>'
            );
            ?>
        </div>
    </div>
</section>

<?php
footer();
?>
