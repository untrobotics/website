<!--Adds page header-->
<?php
require('../../template/top.php');
head('Our-Guides', true);
?>

<main class="page-content">
	<!-- Classic Breadcrumbs-->
	<section class="breadcrumb-classic">
		<div class="rd-parallax">
			<div data-speed="0.25" data-type="media" data-url="/images/headers/about.jpg" class="rd-parallax-layer"></div>
			<div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
				<div class="shell">
					<ul class="list-breadcrumb">
						<li><a href="/">Home</a></li>
						<li><a href="/guides">Guides</a></li>
						<li>Our Guides</li>
					</ul>
				</div>
			</div>
		</div>
	</section>
	<!--Main content-->
	<section class="section-75 section-md-100 section-lg-150">
		<div class="shell">
			<div class="range range-md-justify">
				<div class="cell-md-8 cell-lg-7 cell-xl-6">
					<div class="inset-md-right-30 inset-lg-right-0">
						<h1>Our Guides</h1>
						<h3>Overview</h3>
						<p>Here's a list of some guides written by our UNT Robotics members and alumni.</p>
						<div class="inset-lg-left-25 inset-md-left-15 inset-sm-left-0">
							<a href="" >Intro to embedded programming</a><br>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<section class="section-30 section-sm-75">
		<div class="shell">
			<div class="range"></div>
		</div>
	</section>
</main>
<!--Adds page footer-->
<?php
require ('../../template/footer.php');
?>