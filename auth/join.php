<?php
require('../template/top.php');
require(BASE . '/template/functions/hash.php');
if (!empty($_POST)) {
	$name = $_POST['name'];
	$email = $_POST['email'];
	$phone = preg_replace('/[^0-9]/', '', $_POST['phone_number']);
	$unteuid = strtolower($_POST['unteuid']);
	$grad_term = $_POST['graduation_term'];
	$grad_year = $_POST['graduation_year'];
	$password1 = $_POST['password1'];
	$password2 = $_POST['password2'];

	$valid_grad_terms = array('spring', 'fall', 'summer');

	do {
		if (strlen($name) < 4) {
			$error = "Please enter a valid name";
			break;
		} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error = "Please enter a valid e-mail address";
			break;
		}
		
		$q = $db->query('SELECT id FROM users WHERE email = "' . $db->real_escape_string($email) . '"');
		if ($q->num_rows > 0) {
			$error = "The e-mail address you entered is already in the database.";
			break;
		}
		
		$q = $db->query('SELECT id FROM users WHERE unteuid = "' . $db->real_escape_string($unteuid) . '"');
		if ($q->num_rows > 0) {
			$error = "The UNT EUID you entered is already in the database.";
			break;
		}
		
		if (strlen($phone) != 10) {
			$error = "Please enter a valid U.S. phone number";
			break;
		} else if (!preg_match('/[a-z]{2,3}\d{4}/', $unteuid)) {
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

				// Timezone comes from the browser (hidden field set by Intl.DateTimeFormat)
				// instead of an IP2Location lookup + external API.
				$timezone = $_POST['timezone'] ?? '';
				if (!in_array($timezone, timezone_identifiers_list(), true)) {
					$timezone = TIMEZONE;
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
				$error = 'An internal error occurred, please contact support.';
				AdminBot::send_message("Failed to register new user due to database error: $db->error. Please investigate.");
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
			  <li>Account</li>
			  <li>Join
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
					<input type="hidden" name="timezone" id="reg-timezone">
					<script>try{document.getElementById('reg-timezone').value=Intl.DateTimeFormat().resolvedOptions().timeZone;}catch(e){}</script>
				<?php
					if (isset($error)) {
						?>
						<div class="alert alert-danger text-center" role="alert"><?php echo $error; ?></div>
						<?php
					}
				?>
				  <div class="form-group postfix-xl-right-40">
					<label for="name" class="form-label">Name</label>
					<input id="name" type="text" name="name" data-constraints="@Required" class="form-control" <?php if (isset($_POST['name'])) { echo 'value="' . htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES) . '"'; } ?>>
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="email" class="form-label">E-mail address</label>
					<input id="email" type="text" name="email" data-constraints="@Required" class="form-control" <?php if (isset($_POST['email'])) { echo 'value="' . htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) . '"'; } ?>>
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="phone_number" class="form-label">Phone Number</label>
					<input id="phone_number" type="text" name="phone_number" data-constraints="@Required" class="form-control" <?php if (isset($_POST['phone_number'])) { echo 'value="' . htmlspecialchars($_POST['phone_number'] ?? '', ENT_QUOTES) . '"'; } ?>>
				  </div>
				  <div class="form-group">
					<small style="display:block;position:relative;margin-top:4px;line-height:1.5;font-size:0.8rem;color:#6b6b6b;">
						By providing your phone number and joining, you agree to receive SMS text messages from
						UNT Robotics &mdash; including account/phone verification codes and replies to questions you
						text us. Message frequency varies. Message and data rates may apply. Reply <strong>STOP</strong>
						to opt out or <strong>HELP</strong> for help. We never sell or share your number. See our
						<a href="https://www.untrobotics.com/legal/privacy">Privacy Policy</a> and
						<a href="https://www.untrobotics.com/legal/sms-terms">SMS Terms &amp; Conditions</a>.
					</small>
				  </div>
				  <div class="form-group postfix-xl-right-40">
					<label for="unteuid" class="form-label">UNT EUID</label>
					<input id="unteuid" type="text" name="unteuid" data-constraints="@Required" class="form-control" <?php if (isset($_POST['unteuid'])) { echo 'value="' . htmlspecialchars($_POST['unteuid'] ?? '', ENT_QUOTES) . '"'; } ?>>
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
