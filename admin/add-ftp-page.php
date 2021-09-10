<?php
require('../template/top.php');

if (isset($_POST)) {
    $username = @$_POST['username'];
    $password = @$_POST['password'];

    do {
        if (strlen($username) < 4) {
            echo 'INVALID_NAME';
            break;
        } else if (strlen($password) < 4) {
            echo 'INVALID_PASSWORD';
            break;
        }


        $q = $db->query('INSERT INTO ftpusers (name, passwd)
		VALUES (
			"' . $db->real_escape_string($username) . '",
			PASSWORD("' . $db->real_escape_string($password) . '")
		)
		');



        if ($q) {
            echo 'ADD_SUCCESS';
        } else {
            echo 'ERROR';
        }
    } while (false);
}
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

<main class="page-content">
    <section class="section-50 section-md-75 section-md-100 section-lg-120 section-xl-150 bg-wild-sand">
        <div class="shell text-left">
            <h2>Add FTP user</h2>
            <form action="" method="post">
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
                <div id="add-ftp-user-submit-area">

                    <span><input type="submit" value="Add user to FTP DB" class="btn btn-form btn-default"></span>
                </div>
            </form>
        </div>
    </section>
</main>
<?php
footer();
?>
