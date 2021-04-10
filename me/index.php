<?php
require('../template/top.php');
head('My Profile', true, true);

global $userinfo
?>

<main class="page-content">
	<!-- Classic Breadcrumbs-->
	<section class="section-50">
	  <div class="shell text-sm-left">
		<div class="blog-post">
		  <div class="range">
			<div class="cell-md-preffix-2 cell-lg-9 cell-md-10 cell-xl-8">
			  <h1>Your Profile</h1>
			  <h6>Stuff?</h6>
			  <p>Profile info.</p>
                <p><?php echo $userinfo['id']; ?></p>
			</div>
			<div class="cell-xs-12">
			  <div class="left-aside"><span class="small text-darker text-uppercase text-bold text-spacing-340"></span>
                  <h2>done</h2>
				<div class="divider-custom veil reveal-md-block"></div>
				<ul class="list text-md-center">
				  <li><a href="#" class="ioon icon-sm icon-darker fa-check"></a></li>
				  <li><a href="#" class="ioon icon-sm icon-darker fa-times"></a></li>
				</ul>
			  </div>
			</div>
		  </div>
		</div>
	  </div>
	</section>
  </main>

<?php
footer();
?>