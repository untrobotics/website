<!--Adds page header-->
<?php
require('../template/top.php');
head('Guides', true);
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
						<li>Guides
						</li>
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
						<h1>Guides</h1>
						<h3>Overview</h3>
						<p>Here are some helpful resources to get you started in various robotics, programming, or engineering subjects. Whether you're a competitor in <a href="/botathon">our Botathon competition</a>, or just interested in robotics, this is the place to start!</p>
						<h3>Our Guides</h3>
						<p>These are some guides our team has personally written to help our members!</p>
						<p>--empty--</p>
						<h3>External Guides</h3>
						<p>While not affiliated with UNT Robotics, we think these guides will be just as helpful!</p>
						<p>Insert links here as a list (ask seb for them? or whatever context it was in so I can find it)</p>
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
require ('../template/footer.php');
?>