<?php
require('template/top.php');
head('Authentication', true, false, false, "");

require_once('saml/simplesamlphp/lib/_autoload.php');
$as = new \SimpleSAML\Auth\Simple('untrobotics-sp');
?>
<main class="page-content">
	<section class="section-75 section-md-100 section-lg-150">
		<div class="shell text-sm-left">
			<div class="range">
				<div class="cell-sm-6 cell-lg-5 cell-xl-4">
					<?php
					if (!$as->isAuthenticated()) {
						?>
						<a href="https://saml.untrobotics.com/untsso/login.php" class="btn btn-primary">Login</a>
						<?php
					} else {
						?>
						Auth info from SSO:
						<?php
						var_dump($as->getAttributes());
						?>
						<a href="https://saml.untrobotics.com/untsso/login.php" class="btn btn-primary">Logout</a>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</section>
</main>
<?php
footer();
?>
