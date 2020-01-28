<?php
require('../template/top.php');
head('Order Shirts', true);
?>
<style>	
	.checkbox-container {
	  display: block;
	  position: relative;
	  padding-left: 35px;
	  margin-bottom: 12px;
	  cursor: pointer;
	  -webkit-user-select: none;
	  -moz-user-select: none;
	  -ms-user-select: none;
	  user-select: none;
	}

	/* Hide the browser's default checkbox */
	.checkbox-container input {
	  position: absolute;
	  opacity: 0;
	  cursor: pointer;
	  height: 0;
	  width: 0;
	}

	/* Create a custom checkbox */
	.checkmark {
	  position: absolute;
	  top: 0;
	  left: 0;
	  height: 25px;
	  width: 25px;
	  background-color: #eee;
	}

	/* On mouse-over, add a grey background color */
	.checkbox-container:hover input ~ .checkmark {
	  background-color: #ccc;
	}

	/* When the checkbox is checked, add a blue background */
	.checkbox-container input:checked ~ .checkmark {
	  background-color: #45cd8f;
	}

	/* Create the checkmark/indicator (hidden when not checked) */
	.checkmark:after {
	  content: "";
	  position: absolute;
	  display: none;
	}

	/* Show the checkmark when checked */
	.checkbox-container input:checked ~ .checkmark:after {
	  display: block;
	}

	/* Style the checkmark/indicator */
	.checkbox-container .checkmark:after {
	  left: 9px;
	  top: 5px;
	  width: 5px;
	  height: 10px;
	  border: solid white;
	  border-width: 0 3px 3px 0;
	  -webkit-transform: rotate(45deg);
	  -ms-transform: rotate(45deg);
	  transform: rotate(45deg);
	}
	table#sizes td:first-of-type {
		font-weight: 800;
		padding-right: 25px;
	}
	table#sizes tr:first-of-type {
		padding-bottom: 15px;
	}
	.choose-option + .select2 {
		display: inline-block;
		width: 175px !important;
	}
	#buy-shirt-now {
		display: inline-block;
		margin: 0;
	}
	.select2-container--bootstrap .select2-selection--single .select2-selection__rendered {
		color: #686868;
		padding: 18px;
	}
	@media (max-width: 450px) {
		.choose-option + .select2 {
			display: block;
			width: 100% !important;
		}
		.merch-image img {
			width: 100%;
		}
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
			  <li><a href="/merch">Merch</a></li>
			  <li>Shirts
			  </li>
			</ul>
		  </div>
		</div>
	  </div>
	</section>
	<section class="section-50">
	  <div class="shell">
		<h1 class="text-center text-lg-left">T-Shirts</h1>
		<div class="range range-lg range-xs-center">
		  <div class="cell-lg-12 cell-md-8">
			<div class="range">
			  <div class="cell-lg-12">
				<div class="inset-lg-right-45">
				  <ul class="list list-xl">
					<li>
					  <p><span class="small">UNT Robotics T-Shirt</span><span class="text-darker">Support UNT Robotics &amp; look dapper while doing it!</span></p>
					</li>
				  </ul>
					<div class="col-lg-6 col-sm-12">
					  <h6 style="margin-top: 25px;"><strong>T-shirt specs</strong></h6>
						<ul style="list-style: circle; margin-left: 25px; text-align: left;">
							<li>100% ring-spun combed cotton</li>
							<li>Locally printed</li>
							<li style="list-style: none;">&nbsp;</li>
							<li>8 sizes
								<table id="sizes">
									<tr><td>Size</td><td>Chest Length</td></tr>
									<tr><td>XS</td><td>32"-34"</td></tr>
									<tr><td>S</td><td>35"-36"</td></tr>
									<tr><td>M</td><td>37"-39"</td></tr>
									<tr><td>L</td><td>40"-42"</td></tr>
									<tr><td>XL</td><td>43"-45"</td></tr>
									<tr><td>2XL</td><td>46"-48"</td></tr>
									<tr><td>3XL</td><td>49"-51"</td></tr>
									<tr><td>4XL</td><td>52"-54"</td></tr>
								</table>
							</li>
							<li>2 colours
								<table id="sizes">
									<tr><td>Black</td></tr>
									<tr><td>Green</td></tr>
								</table>
							</li>
						</ul>
				  	</div>
					<div class="col-lg-6 col-sm-12">
						<div class="merch-image">
							<a href="/images/merch/robotics-shirts-preview-large-both.png"><img src="/images/merch/robotics-shirts-preview-small-both.png"/></a>
						</div>
					</div>
					<div class="col-lg-12 text-center">
						<form id="buy-shirt-form" action="/merch/buy/shirt" method="GET">
							<div style="display: inline-block; max-width: 525px;">
							<label class="checkbox-container"> I have paid dues for this semester <em>(not required)</em>.
							  <input name="code" value="r5tmp" type="checkbox">
							  <span class="checkmark"></span>
							</label></div>
							<br>
							<select id="choose-colour" class="choose-option" name="colour">
								<option value="none">Choose colour...</option>
								<option value="black">Black</option>
								<option value="green">Green</option>
							</select><!--
							no whitespace between inline elements
							--><select id="choose-size" class="choose-option" name="size">
								<option value="none">Choose size...</option>
								<option value="xs">XS</option>
								<option value="s">S</option>
								<option value="m">M</option>
								<option value="l">L</option>
								<option value="xl">XL</option>
								<option value="2xl">2XL</option>
								<option value="3xl">3XL</option>
								<option value="4xl">4XL</option>
							</select><!--
							no whitespace between inline elements
							--><button type="submit" id="buy-shirt-now" class="btn btn-primary offset-top-30 offset-lg-top-70">Buy Now</button>
						</form>
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