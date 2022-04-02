<?php
require('../template/top.php');

if (isset($_POST)) {



    do {
        if (isset($_POST['userid'])){
            $id = $_POST['userid'];

            $q = $db->query('SELECT * FROM ftpusers WHERE id = "' . $db->real_escape_string($id) . '"');

            $user = $q->fetch_assoc();
            $username = $user['name'];

            unlink(FTP_USER_CONFIG_DIR . $username);

            $q = $db->query('DELETE FROM ftpusers WHERE id = "' . $db->real_escape_string($id) . '"');
            var_dump($q);
        }
    } while (false);

    do {
        if (isset($_POST['username'])){
            $username = $_POST['username'];
        } else {
            echo 'NO_NAME';
            break;
        }

        if (isset($_POST['password'])){
            $password = $_POST['password'];
        } else {
            echo 'NO_PASSWD';
            break;
        }

        if (strlen($username) == 0){
            echo 'INVALID_USERNAME';
            break;
        }

        if (strlen($password) < 5){
            echo 'INVALID_PASSWD';
            break;
        }


        $q = $db->query('INSERT INTO ftpusers (name, passwd)
		VALUES (
			"' . $db->real_escape_string($username) . '",
		    PASSWORD("' . $db->real_escape_string($password) . '")
		)');

        copy(FTP_USER_CONFIG_FILE, FTP_USER_CONFIG_DIR . $username);

        if ($q) {
            echo 'ADD_SUCCESS';
        } else {
            echo 'ERROR';
        }
    } while (false);
}

    $q = $db->query("SELECT * FROM ftpusers");

?>

<html>
<head>
</head>
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
                <table>
                        <tr>
                            <th>Name</th>
                            <th>ID</th>
                        </tr>
                    <?php
                    while ($user = $q->fetch_array(MYSQLI_ASSOC)) {

                        // name
                        // id

                        ?>
                            <tr>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['id']; ?></td>
                                <td><form method="post"><button name = "userid" value = "<?php echo $user['id']; ?>" type="submit">X</button></form><td>
                            </tr>
                        <?php
                    }?>
                </table>
        </div>
    </section>
</main>
</html>
