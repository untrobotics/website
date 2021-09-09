<?php
require('../template/top.php');
require('../api/groupme-funcs.php');

if (isset($_POST)) {
	$username = @$_POST['username'];
	$password = @$_POST['password'];

	do {
		$response = json_decode(file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . GOOGLE_RECAPTCHA_KEY . '&response='.$captcha."&remoteip=".$_SERVER['REMOTE_ADDR']), true);
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
			echo 'CONTACT_SUCCESS';
		} else {
			echo 'ERROR';
		}
	} while (false);
}

?>
