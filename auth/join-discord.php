<?php
require('../template/top.php');
head('Join us on Discord', true);

$log = var_export($_REQUEST, true);
error_log($log, 3, BASE . '/paypal/logs/pdt-dues.log');
?>
<style>
    .alert {
        background-color: #4695d9;
        color: white;
    }
    .alert-inline > * {
        vertical-align: middle;
    }
</style>

<main class="page-content">
    <section class="section-50 section-md-75 section-lg-100">
        <div class="shell">
            <div class="range range-md-justify">
                <div class="cell-md-12">
                    <div class="inset-md-right-30 inset-lg-right-0 text-center">
                        <h1>Join us on Discord</h1>

                        <div class="alert alert-info alert-inline">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                            </svg> Note: If the following link says you are 'banned from this guild', please disconnect from campus wifi and try again.
                        </div>

                        <div><a class="btn btn-default" href="<?php echo DISCORD_INVITE_URL; ?>">Click here to join our discord server.</a></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
footer();
?>
