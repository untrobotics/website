<?php
require('../template/top.php');
head('Merch', true, false, false, "Support UNT Robotics & look dapper while doing it!");
?>
<style>
	.page-index-listing li {
		list-style: circle;
	}
	.text-heading {
		display: block;
    	margin-bottom: 2px;
    	font-size: 30px;
    	text-transform: uppercase;
		letter-spacing: .340em;
		text-align: center;
	}
	.text-lower-heading {
		display: block;
    	margin-bottom: 2px;
		color: #212121;
		text-align: center;
	}
	.merch-btn {
  		display: block;
  		width: 70%;
  		border: none;
  		background-color: #6C6C6C;
  		padding: 15px 20px;
  		font-size: 40px;
  		font-family: 'Kanit';
  		color: #FFFFFF;
  		cursor: pointer;
  		text-align: center;
  		margin: auto;
		line-height: 1.0;
	}
	.merch-btn:hover {
		background-image: none;
		background-color: #45cd8f;
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
						<p>
							<span class="text-heading">UNT Robotics Merchandise</span>
							<span class="text-lower-heading">Support UNT Robotics &amp; look dapper while doing it!</span>
						</p>
					</li>
				  </ul>
					<div class="range range-lg-center">
						<div class="cell-lg-10 cell-sm-12">
							<a href="shirts-hoodies"><button type="button" class="merch-btn">Shirts &amp; Hoodies</button></a></br>
							<a href="hats"><button type="button" class="merch-btn">Hats</button></a></br>
                            <a href="trousers"><button type="button" class="merch-btn">Trousers</button></a></br>
                            <a href="gear"><button type="button" class="merch-btn">Gear</button></a></br>
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