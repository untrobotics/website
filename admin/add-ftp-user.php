<?php
require('../template/top.php');
head('Add FTP user', true);
?>
<style>
    #contact-form-submit-area {
        margin-top: 10px;
    }

    #contact-form-submit-area > span > * {
        display: inline-block;
        vertical-align: middle;
    }
</style>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<main class="page-content">
    <section class="section-50 section-md-75 section-md-100 section-lg-120 section-xl-150 bg-wild-sand">
        <div class="shell text-left">
            <h2>just fill in the form to add FTP user</h2>
            <form action="../ajax/add-ftp-user.php" method="post">
                <div class="range offset-top-40 offset-md-top-120">
                    <div class="cell-lg-4 cell-md-6">
                        <div class="form-group postfix-xl-right-40">
                            <label for="username" class="form-label">Username *</label>
                            <input id="username" type="text" name="username" data-constraints="@Required"
                                   class="form-control">
                        </div>
                        <div class="form-group postfix-xl-right-40">
                            <label for="password" class="form-label">Password *</label>
                            <input id="password" type="text" name="password" data-constraints="@Required"
                                   class="form-control">
                        </div>
                    </div>
                </div>
                <div id="contact-form-submit-area">

                    <span><input type="submit" value="Add user to FTP DB" class="btn btn-form btn-default"></input></span>
                </div>
            </form>
        </div>
    </section>
</main>
<?php
footer();
?>
