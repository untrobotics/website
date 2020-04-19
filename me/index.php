<?php
if (!isset($_GET['seb'])) {
	die('in development');
}
require('../template/top.php');
head('My Profile', true, true);
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
			</div>
			<div class="cell-xs-12">
			  <div class="left-aside"><span class="small text-darker text-uppercase text-bold text-spacing-340"><?php echo $userinfo['name']; ?></span>
				<div class="divider-custom veil reveal-md-block"></div>
				<ul class="list text-md-center">
				  <li><a href="#" class="ioon icon-sm icon-darker fa-check"></a></li>
				  <li><a href="#" class="ioon icon-sm icon-darker fa-times"></a></li>
				</ul>
			  </div>
			</div>
		  </div>
		</div>
		<div class="blog-post-navigation">
		  <div class="range">
			<div class="cell-md-preffix-2 cell-md-5 cell-sm-6 text-sm-left cell-lg-4">
			  	<h6>
					<a href="#">
						<span class="prev">PREV</span><br>
				  		<span class="preffix-sm-left-30 reveal-inline-block preffix-xl-left-40">How Plastics Influence Sports: an<br class="veil reveal-xl-block">	Independent Study by Plastic Goods</span>
					</a>
				</h6>
			</div>
			<div class="cell-md-5 cell-sm-6 text-sm-right cell-lg-4 offset-top-20 offset-sm-top-0">
			  <h6>
				  <a href="#">
					  <span class="next">NEXT</span><br>
				  	<span class="reveal-inline-block">Making Robots Safer Around People<br class="veil reveal-xl-block">
						Without Sacrificing Performance</span>
				  </a>
			  </h6>
			</div>
		  </div>
		</div>
	  </div>
	</section>
  </main>

<?php
footer();
?>