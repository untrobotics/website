<?php
//header("Location: https://discord.gg/j3D5Zdp");
require('../template/top.php');
head('Welcome', true, true);
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
		<div data-speed="0.25" data-type="media" data-url="https://targetcareers.co.uk/sites/targetcareers.co.uk/files/public/styles/header_1500x550/public/field/image/engineering-personal-statements.jpg?itok=I8sIDavS" class="rd-parallax-layer"></div>
		<div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
		  <div class="shell">
			<ul class="list-breadcrumb">
			  <li><a href="/">Home</a></li>
			  <li><a href="/join">Join</a></li>
			  <li>Welcome
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
			<h2>Welcome to <strong>UNT Robotics</strong></h2>
			<p>Please make sure to <a href='/join/discord'>join us on Discord</a> and <a href='/join/campuslabs'>become a member on CampusLabs</a>.</p>
		  </div>
		</div>
	  </div>
	</section>
</main>
<?php
footer();
?>