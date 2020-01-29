<?php
require('../template/top.php');
require(BASE . '/template/functions/hash.php');
if (!empty($_POST)) {
	require(BASE . '/template/functions/IP2Location.php');

	$name = $_POST['name'];
	$email = $_POST['email'];
	$phone = preg_replace('/[^0-9]/', '', $_POST['phone_number']);
	$unteuid = $_POST['unteuid'];
	$grad_term = $_POST['graduation_term'];
	$grad_year = $_POST['graduation_year'];
	$password1 = $_POST['password1'];
	$password2 = $_POST['password2'];

	$valid_grad_terms = array('spring', 'summer', 'fall');

	do {
		if (strlen($name) < 4) {
			$error = "Please enter a valid name";
			break;
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error = "Please enter a valid e-mail address";
			break;
		}
		
		$q = $db->query('SELECT * FROM users WHERE email = "' . $db->real_escape_string($email) . '"');
		if ($q->num_rows > 0) {
			$error = "The e-mail address you entered is already in the database.";
			break;
		} else if (strlen($phone) != 10) {
			$error = "Please enter a valid U.S. phone number";
			break;
		} else if (!preg_match('/[a-z]{2,3}\d{4}/i', $unteuid)) {
			$error = "Please enter a valid UNT EUID, e.g. abc1234";
			break;
		} else if (!in_array($grad_term, $valid_grad_terms)) {
			$error = "Please choose a valid graduation term";
			break;
		} else if (intval($grad_year) < intval(date('Y'))) {
			$error = "Please choose a valid graduation year";
			break;
		} else if (empty($password1)) {
			$error = "You must enter a password";
			break;
		} else if ($password1 !== $password2) {
			$error = "The passwords you entered do not match";
			break;
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
			$ipdb = FALSE;
			if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				$ipdb = new \IP2Location\Database("$base/ip2location/IP2LOCATION-LITE-DB11.BIN", \IP2Location\Database::FILE_IO);
			} else if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				$ipdb = new \IP2Location\Database("$base/ip2location/IP2LOCATION-LITE-DB11.IPV6.BIN", \IP2Location\Database::FILE_IO);
			}
			if ($ipdb == FALSE) {
				$timezone = "UTC";
			} else {
				$ipinfo = $ipdb->lookup($_SERVER['REMOTE_ADDR'], \IP2Location\Database::ALL);
				$tzdb = file_get_contents("http://api.timezonedb.com?key=" . TIMEZONEDB_API_KEY . "&lat={$ipinfo['latitude']}&lng={$ipinfo['longitude']}&format=json");
				$timezone = json_decode($tzdb);
				$timezone = $timezone->zoneName;
				if (!in_array($timezone, timezone_identifiers_list())) {
					$timezone = "UTC";
				}
			}

			// do query
			$q = $db->query('INSERT INTO users (name, email, phone, unteuid, grad_term, grad_year, password, reg_timestamp, reg_ip, timezone)
			VALUES (
				"' . $db->real_escape_string($name) . '",
				"' . $db->real_escape_string($email) . '",
				"' . $db->real_escape_string($phone) . '",
				"' . $db->real_escape_string($unteuid) . '",
				"' . $db->real_escape_string(array_search($grad_term, $valid_grad_terms)) . '",
				"' . $db->real_escape_string($grad_year) . '",
				"' . $db->real_escape_string(password_hash($password1, PASSWORD_BCRYPT, array('cost' => 12))) . '",
				NOW(),
				"' . $db->real_escape_string($ip) . '",
				"' . $db->real_escape_string($timezone) . '"
			)
			');

			if ($q) {
				// set cookies
				$fingerprint = get_fingerprint();
				$auth_session_id = obfuscate_hash(sha1($fingerprint . session_id())); // based on IP, time, /dev/urandom and a PHP PRNG (PLCG) and fingerprint calculated above
				session_regenerate_id();
				$auth_session_name = obfuscate_hash(bin2hex(random_bytes(32))); // just really random

				$db->query("INSERT INTO auth_sessions
					(session_id,
					session_name,
					fingerprint,
					uid,
					expires)

					VALUES
					('".$db->real_escape_string($auth_session_id)."',
					'".$db->real_escape_string($auth_session_name)."',
					'".$db->real_escape_string($fingerprint)."',
					'".$db->real_escape_string($db->insert_id)."',
					'".$db->real_escape_string(0)."')
				") or die($db->error); // remove this for security

				setcookie(COOKIE_PREFIX . '_SESSION_ID', $auth_session_id, 0, '/', WEBSITE_DOMAIN, true, true);
				setcookie(COOKIE_PREFIX . '_SESSION_NAME', $auth_session_name, 0, '/', WEBSITE_DOMAIN, true, true);

				header('Location: /auth/welcome');
			} else {
				$error = 'An internal error occurred, please contact support' . $db->error;
			}
		}
	} while (false);
}
head('Join', true);
?>
<style>
	.select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
		color: #686868;
		padding: 18px;
	}
</style>
<main class="page-content">
	<!-- Classic Breadcrumbs-->
	<section class="breadcrumb-classic">
	  <div class="rd-parallax">
		<div data-speed="0.25" data-type="media" data-url="/images/headers/login.jpg" class="rd-parallax-layer"></div>
		<div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
		  <div class="shell">
			<ul class="list-breadcrumb">
			  <li><a href="/">Home</a></li>
			  <li><a href="/auth/">Auth</a></li>
			  <li>Login
			  </li>
			</ul>
		  </div>
		</div>
	  </div>
	</section>
	<section class="section-50">
	  <div class="shell">
		<div class="range offset-top-40">
		  <div class="cell-xl-12 cell-lg-12 cell-md-12 cell-sm-12 text-left">
			<h2>Join</h2>
			<form data-form-output="form-output-global" data-form-type="login" method="post" action="" class="rd-mailform text-left">
				<?php
					if (isset($error)) {
						?>
						<div class="alert alert-danger text-center" role="alert"><?php echo $error; ?></div>
						<?php
					}
				?>
				  <div class="form-group postfix-xl-right-40">
					<label for="name" class="form-label">Name</label>
					<input id="name" type="text" name="name" data-constraints="@Required" class="form-control" <?php if (isset($_POST['name'])) { echo 'value="' . $_POST['name'] . '"'; } ?>>
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="email" class="form-label">E-mail address</label>
					<input id="email" type="text" name="email" data-constraints="@Required" class="form-control" <?php if (isset($_POST['email'])) { echo 'value="' . $_POST['email'] . '"'; } ?>>
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="phone_number" class="form-label">Phone Number</label>
					<input id="phone_number" type="text" name="phone_number" data-constraints="@Required" class="form-control" <?php if (isset($_POST['phone_number'])) { echo 'value="' . $_POST['phone_number'] . '"'; } ?>>
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="unteuid" class="form-label">UNT EUID</label>
					<input id="unteuid" type="text" name="unteuid" data-constraints="@Required" class="form-control" <?php if (isset($_POST['unteuid'])) { echo 'value="' . $_POST['unteuid'] . '"'; } ?>>
				  </div>
				  <div class="postfix-xl-right-40">
					<!--<label for="graduation_date" class="form-label">Graduation Date</label>-->
					  <select id="graduation_term" name="graduation_term" class="">
						  <option>Select graduation term...</option>
						  <option value="spring" <?php if (isset($_POST['graduation_term']) && $_POST['graduation_term'] === "spring") { echo 'selected="selected"'; } ?>>Spring</option>
						  <option value="summer" <?php if (isset($_POST['graduation_term']) && $_POST['graduation_term'] === "summer") { echo 'selected="selected"'; } ?>>Summer</option>
						  <option value="fall" <?php if (isset($_POST['graduation_term']) && $_POST['graduation_term'] === "fall") { echo 'selected="selected"'; } ?>>Fall</option>
					  </select>
					  <select id="graduation_year" name="graduation_year" class="">
					  	  <option>Select graduation year...</option>
						  <?php
						  for ($i = 0; $i < 7; $i++) {
							$year = date('Y', strtotime("+{$i} years"));
							?>
								<option value="<?php echo $year; ?>" <?php if (isset($_POST['graduation_year']) && $_POST['graduation_year'] === $year) { echo 'selected="selected"'; } ?>><?php echo $year; ?></option>
						  	<?php
						  }
						  ?>
					  </select>
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="password1" class="form-label">Password</label>
					<input id="password1" type="password" name="password1" data-constraints="@Required" class="form-control">
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="password2" class="form-label">Confirm Password</label>
					<input id="password2" type="password" name="password2" data-constraints="@Required" class="form-control">
				  </div>
			  <button type="submit" class="btn btn-default offset-top-35">Create Account</button>
			</form>
		  </div>
		</div>
	  </div>
	</section>
</main>
<?php
footer();
?>
