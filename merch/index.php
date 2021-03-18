<?php
require('../template/top.php');
head('Merch', true);
?>
<style>
	.page-index-listing li {
		list-style: circle;
	}
</style>
<main class="page-content">
	<!-- Classic Breadcrumbs-->
	<section class="breadcrumb-classic">
	  <div class="rd-parallax">
		<div data-speed="0.25" data-type="media" data-url="/images/headers/shirts.jpg" class="rd-parallax-layer"></div>
		<div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
		  <div class="shell">
			<ul class="list-breadcrumb">
			  <li><a href="/">Home</a></li>
			  <li>Merch
			  </li>
			</ul>
		  </div>
		</div>
	  </div>
	</section>
	<section class="section-50">
	  <div class="shell">
		<h1 class="text-center text-lg-left">Merch</h1>
		<div class="range range-lg range-xs-center">
		  <div class="cell-lg-12 cell-md-8">
			<div class="range">
			  <div class="cell-lg-12">
				<div class="inset-lg-right-45">
				  <ul class="list list-xl">
					<li>
					  <p><span class="small">UNT Robotics Merchandise</span><span class="text-darker">Support UNT Robotics &amp; look dapper while doing it!</span></p>
					</li>
				  </ul>
					<div class="range range-lg-center">
						<div class="cell-lg-10 cell-sm-12">
							<ul class="page-index-listing">
								<li><a href="shirts-hoodies">Shirts &amp; Hoodies</a></li>
								<li><a href="hats">Hats</a></li>
                                <li><a href="trousers">Trousers</a></li>
                                <li><a href="gear">Gear</a></li>
							</ul>
						</div>
					</div>
				</div>
			  </div>
			</div>
		  </div>
		</div>
	  </div>
	</section>
</main>
<?php
footer(false);
?>
<script>
	$('#buy-shirt-form').on('submit', function(e) {
		if ($('#choose-size').val() == 'none') {
			alert('Please choose a size.');
			return false;
		}
		if ($('#choose-colour').val() == 'none') {
			alert('Please choose a colour.');
			return false;
		}
	});
</script>