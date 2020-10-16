<?php
require('../../template/top.php');
require_once(BASE . '/api/discord/bots/admin.php');
head('Thank you for your donation', true);

AdminBot::send_message("Donation received (probably)!");
?>

    <main class="page-content">
        <section class="section-50 section-md-75 section-lg-100">
            <div class="shell">
                <div class="range range-md-justify">
                    <div class="cell-md-12">
                        <div class="inset-md-right-30 inset-lg-right-0 text-center">
                            <h1>Donation Received</h1>

                            <p><strong>Thank you for your donation! It will go a long way to helping us support, teach and mentor young engineers at UNT.</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php
footer();
?>