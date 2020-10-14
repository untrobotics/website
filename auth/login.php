<?php
require("../template/top.php");
require(BASE . "/template/functions/hash.php");
if (isset($_POST['email'])) {
	do {
		$required = array("email", "password");
		foreach ($required as $key => $val) {
			if (!isset($_POST[$val]) || !strlen($_POST[$val]) > 0) {
				$error = "You must define a $val.";
				break 2;
			}
		}
		$q = $db->query("SELECT * FROM `users` WHERE email = '".$db->real_escape_string($_POST['email'])."' LIMIT 1");
		if (!$q->num_rows > 0) {
			$error = "Incorrect email or password.";
			break;
		} else {
			$r = $q->fetch_array(MYSQLI_ASSOC);
		}
		if (!password_verify($_POST['password'], $r['password'])) {
			$error = "Incorrect email or password.";
			break;
		} else {
			if (@$_POST['remember-me'] == 1) {
				$expires = strtotime("+1 year"); // almost never?
			} else {
				$expires = 0;
			}
			$fingerprint = get_fingerprint();
			$auth_session_id = obfuscate_hash(sha1($fingerprint . session_id())); // based on IP, time, /dev/urandom and a PHP PRNG (PLCG) and fingerprint calculated above
			session_regenerate_id();
			// possibly an odd solution but it works, the hashes are random and hard to read and should be relatively unique per device
			$auth_session_name = obfuscate_hash(bin2hex(random_bytes(32))); // just really random
			
			$db->query("INSERT INTO auth_sessions
			(session_id,
			session_name,
			fingerprint,
			uid,
			expires)
			VALUES
			(
				'".$db->real_escape_string($auth_session_id)."',
				'".$db->real_escape_string($auth_session_name)."',
				'".$db->real_escape_string($fingerprint)."',
				'".$db->real_escape_string($r['id'])."',
				'".$db->real_escape_string($expires)."'
			)
			") or die($db->error); // remove this for security

			setcookie(COOKIE_PREFIX . "_SESSION_ID", $auth_session_id, $expires, '/', WEBSITE_DOMAIN, true, true);
			setcookie(COOKIE_PREFIX . "_SESSION_NAME", $auth_session_name, $expires, '/', WEBSITE_DOMAIN, true, true);
			setcookie("remember", encode_hash($r['id']), time()*2, '/auth/', WEBSITE_DOMAIN);

			if (isset($_GET['returnto']) && !preg_match("@^/auth@", $_GET['returnto'])) {
				header("Location: //{$_SERVER['SERVER_NAME']}/".preg_replace("@^/@i", "", $_GET['returnto']));
			} else {
				header("Location: /me/");
			}
			die();
		}
	} while (false);
}
head('Login', true);
?>

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
              <div class="cell-md-12 text-center">
                <h2>Login</h2>
				  <?php if (isset($error)) {
						echo "<center><div class='alert alert-danger' style='width:auto; display:inline-block; margin-bottom: 25px;'>$error</div></center>";
					} ?>
                <form data-form-output="form-output-global" data-form-type="login" method="post" action="" class="rd-mailform text-left">
					<center>
						<div style="max-width: 500px;">
						  <div class="form-group">
							<label for="email" class="form-label">E-mail address</label>
							<input id="email" type="email" name="email" data-constraints="@Required" class="form-control">
						  </div>
						  <div class="form-group">
							<label for="password" class="form-label">Password</label>
							<input id="password" type="password" name="password" data-constraints="@Required" class="form-control">
						  </div>
							<div style="width: 155px;">
							  <div class="form-group">
								<label class="checkbox-container"> Remember Me
								  <input name="remember-me" type="checkbox" class="form-control form-control-has-validation form-control-last-child" value="1" <?php if (@$_POST['remember-me'] || @$_COOKIE['remember'] == "true") { echo "checked='checked'"; } ?>>
								  <span class="checkmark"></span>
								</label>
							  </div>
							</div>
						</div>
                  		<button type="submit" class="btn btn-default offset-top-35">Sign in</button>
						
						<p style="margin-top: 40px;"><a href='/auth/join'>Create an Account</a> <span>-</span> <a href='/auth/forgot-password'>Forgot password?</a></p>
					</center>
                </form>
              </div>
            </div>
          </div>
        </section>
	</main>

<?php
footer();
?>

<body>
	<div id="container">
       
		<div class="cls-content">
			<div class="cls-content-sm panel">
				<div class="panel-body">
					<p class="pad-btm">Sign in to your account</p>
					<form action="" method="POST">
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><i class="fa fa-user"></i></div>
								<input type="text" class="form-control" placeholder="Username" name="username" value="<?php echo @$_POST['username']; ?>">
							</div>
						</div>
						<div class="form-group">
							<div class="input-group">
								<div class="input-group-addon"><i class="fa fa-asterisk"></i></div>
								<input type="password" class="form-control" placeholder="Password" name="password" value="<?php echo @$_POST['password']; ?>">
							</div>
						</div>
						<div class="row">
							<div class="col-xs-8 text-left checkbox">
								<label class="form-checkbox form-icon">
								<input type="checkbox" name="remember-me" value="1" <?php if (@$_POST['remember-me'] || @$_COOKIE['remember'] == "true") { echo "checked='checked'"; } ?>> Remember me
								</label>
							</div>
							<div class="col-xs-4">
								<div class="form-group text-right">
								<button class="btn btn-primary text-uppercase" type="submit">Sign In</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="pad-ver">
				<a href="forgot-password" class="btn-link mar-rgt">Forgot password?</a>
			</div>
		</div>
	</div>
