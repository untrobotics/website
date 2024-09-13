<?php
require('../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
head('Pay Dues', true);

global $db, $untrobotics, $userinfo;
$q = $db->query("SELECT `key`,`value` FROM dues_config WHERE `key` = 'semester_price' OR `key` = 't_shirt_dues_purchase_price'");
if (!$q || $q->num_rows !== 2) {
    AdminBot::send_message("Unable to determine the dues payment price");
    throw new RuntimeException("Unable to determine dues payment price");
}
$r = $q->fetch_all(MYSQLI_ASSOC);

$mapped_config = array();
array_walk(
    $r,
    function (&$val, $_key) use (&$mapped_config) {
        $mapped_config[$val['key']] = $val['value'];
    }
);

$t_shirt_dues_purchase_price = $mapped_config['t_shirt_dues_purchase_price'];
$single_semester_dues_price = $mapped_config['semester_price'];
$full_year_dues_price = $single_semester_dues_price * 2;
$current_term = $untrobotics->get_current_term();
$next_term = $untrobotics->get_next_term();

// only allow the user to pay for the full year if it is the autumn semester
$permit_full_year_payment = $current_term == Semester::AUTUMN;
?>

<style>
    label.checkbox-container {
        display: inline-block;
    }

    .dues-payment-button {
        display: inline-block;
    }

    .dues-payment-button.two-semesters {
        display: none;
    }

    .select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
        color: #686868;
        padding: 18px;
    }

    label.checkbox-container {
        display: inline-block;
    }

    .dues-payment-button {
        display: inline-block;
    }

    .dues-payment-button.two-semesters {
        display: none;
    }

    .dues-shirt-preview {
        display: inline-block;
        margin: 0 auto;
    }

    .dues-shirt-preview img {
        width: 300px;
    }
</style>

<main class="page-content">
    <section class="section-50 section-md-75 section-lg-100">
        <div class="shell">
            <div class="range range-md-justify">
                <div class="cell-md-12">
                    <div class="inset-md-right-30 inset-lg-right-0 text-center">
                        <h1>Pay Dues</h1>
                        <?php
                        if (is_current_user_authenticated()) {
                            if (!$untrobotics->is_user_in_good_standing($userinfo)) {
                                ?>

                                <p>Please use the Pay Now button to pay your dues via PayPal. Dues are per semester,
                                    however you can choose to pay for the whole year at once.</p>
                                <p style="margin-top: 0px;">Once you have paid, your Discord account will automatically
                                    be given the <em>Good Standing</em> role.</p>

                                <?php
                                if ($permit_full_year_payment) {
                                    ?>
                                    <div class="offset-top-20">
                                        <div class="form-group">
                                            <label class="checkbox-container"> Pay for both Spring &amp; Fall?
                                                <input id="full-year" autocomplete="off" name="full-year"
                                                       type="checkbox"
                                                       class="form-control form-control-has-validation form-control-last-child checkbox-custom"
                                                       value="1" item="Dues"><span class="checkbox-custom-dummy"></span>
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                        <div class="form-group">
                                            <label class="checkbox-container">
                                                <div>Order a T-shirt with your dues? (50% off!)</div>
                                                <div><small>(The shirt will be shipped to the shipping address you
                                                        select during payment)</small></div>
                                                <div class="dues-shirt-preview">
                                                    <a href="/images/dues-shirt.png" target="_blank">
                                                        <img src="/images/dues-shirt.png"/>
                                                    </a>
                                                </div>
                                                <select id="include-tshirt" name="include-tshirt" class="">
                                                    <option value="" selected="selected">No T-shirt</option>
                                                    <!--<option value="632b8e41a865f1">XS</option>
                                                    <option value="632b8e41a86664">S</option>
                                                    <option value="632b8e41a866a1">M</option>
                                                    <option value="632b8e41a866e2">L</option>
                                                    <option value="632b8e41a86724">XL</option>
                                                    <option value="632b8e41a86761">2XL</option>
                                                    <option value="632b8e41a867a9">3XL</option>
                                                    <option value="632b8e41a867e6">4XL</option>-->
                                                    <option value="Dues Shirt" variant="XS">XS</option>
                                                    <option value="Dues Shirt" variant="S">S</option>
                                                    <option value="Dues Shirt" variant="M">M</option>
                                                    <option value="Dues Shirt" variant="L">L</option>
                                                    <option value="Dues Shirt" variant="XL">XL</option>
                                                    <option value="Dues Shirt" variant="2XL">2XL</option>
                                                    <option value="Dues Shirt" variant="3XL">3XL</option>
                                                    <option value="Dues Shirt" variant="4XL">4XL</option>
                                                </select>
                                            </label>
                                        </div>
                                    </div>
                                    <?php
                                }?>
                                <p><strong style="font-size: 20px;">
                                        <pre style="display: inline-block;border-radius: 10px;">Cost: <span
                                                    id="dues_cost">$<?php echo $single_semester_dues_price; ?></span></pre>
                                    </strong></p>

                                <?php
                                require_once('../template/functions/paypal.php');
                                if($permit_full_year_payment)
                                    get_payment_button('Pay Now', ['#include-tshirt', '#full-year'], 'dues/paid', 'dues');
                                else
                                    get_payment_button_constant('Pay Now', ['Dues'], ['1 semester'], 'dues/paid', 'dues');
                                ?>
                                <!--<div class="dues-payment-button">
                                    Loading...
                                </div>-->

                                <?php
                            } else {
                                ?>
                                <div class="alert alert-info alert-inline">You have already paid your dues for this
                                    semester. :&#41;
                                </div>
                                <?php
                            }
                        } else {
                            ?>
                            <div class="alert alert-info alert-inline">You must <a
                                        href="/auth/login?returnto=<?php echo urlencode('/dues'); ?>">log in</a> to pay
                                dues.
                            </div>
                            <?php
                        }
                        ?>

                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
footer(false);
?>

<script>
    const cssLoader = `<div class="cssload-loader">
              <div class="cssload-inner cssload-one"></div>
              <div class="cssload-inner cssload-two"></div>
              <div class="cssload-inner cssload-three"></div>
            </div>`;

    const single_semester_price = <?php echo intval($single_semester_dues_price); ?>;
    const full_semester_price = <?php echo intval($full_year_dues_price); ?>;
    const t_shirt_price = <?php echo intval($t_shirt_dues_purchase_price); ?>;

    let fullYear = false;
    let tShirt = null;

    function setNewButton() {
        $('.dues-payment-button').html(cssLoader);
        $.get(`/api/paypal/buttons/generator?t-shirt=${tShirt || ''}&full-year=${fullYear}`, (data) => {
            if (data.cost !== getDuesCost()) {
                $('.dues-payment-button').html('<div class="alert alert-danger">Unable to load payment button.</div>');
                return;
            }
            $('.dues-payment-button').html(data.button);
        })
            .fail(error => {
                $('.dues-payment-button').html('<div class="alert alert-danger">Unable to load payment button.</div>');
            })
    }

    $(document).ready(function () {
        setNewButton();
        $('input[name="full-year"]').attr('variant', '1 Semester')
        $('#include-tshirt').attr('item', "")
    })

    function getDuesCost() {
        let cost = 0;
        if (fullYear) {
            cost += full_semester_price;
        } else {
            cost += single_semester_price
        }
        if (tShirt) {
            cost += t_shirt_price;
        }
        return cost;
    }

    $('input[name="full-year"]').on("change", function (e) {
        fullYear = !!$(this).is(':checked');
        $("#dues_cost").text("$" + getDuesCost());
        // setNewButton();
        if (fullYear) {
            $(this).attr('variant', '2 Semester')
        } else {
            $(this).attr('variant', '1 Semester')
        }
    });

    $('#include-tshirt').on('change', function (e) {
        tShirt = e.target.value || null;
        $("#dues_cost").text("$" + getDuesCost());
        if (tShirt) {
            $(this).attr('item', e.target.value)
        }
    })
</script>