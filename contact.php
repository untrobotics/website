<?php
require('template/top.php');
head('Contact Us', true);
?>
<style>
	#contact-form-submit-area {
		margin-top: 10px;
	}
	#contact-form-submit-area > span > * {
		display: inline-block;
		vertical-align: middle;
	}
</style>

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<main class="page-content">
	<!-- Classic Breadcrumbs-->
	<section class="breadcrumb-classic">
	  <div class="rd-parallax">
		<div data-speed="0.25" data-type="media" data-url="/images/contact-us-header.jpg" class="rd-parallax-layer"></div>
		<div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
		  <div class="shell">
			<ul class="list-breadcrumb">
			  <li><a href="/">Home</a></li>
			  <li>Contact Us</li>
			</ul>
		  </div>
		</div>
	  </div>
	</section>
	<section class="section-75 section-md-100 section-lg-150">
	  <div class="rd-map-wrap">
		<div class="shell text-sm-left">
		  <div class="range">
			<div class="cell-sm-6 cell-lg-5 cell-xl-4">
			  <h1>Get in touch</h1>
			  <h6 class="offset-md-top-35">Stop by or drop us a message anytime!</h6><span class="small text-spacing-340 text-uppercase text-regular offset-top-40 offset-md-top-60">How to reach us</span>
			  <ul class="list offset-top-20 list-lg-middle text-left">
				<li>
				  <div class="unit unit-horizontal unit-spacing-md">
					<div class="unit-left"><span class="icon icon-primary fa-map-marker"></span></div>
					<div class="unit-body">
					  <h6><a href="#" class="text-darker">
						  <strong>UNT Robotics</strong><br>
						  University of North Texas<br>
						  Department of Computer Science and Engineering<br>
						  1155 Union Circle #311366<br>
						  Denton, TX 76203-5017<br>
						  United States
						  </a>
					  </h6>
					</div>
				  </div>
				</li>
				<li>
				  <div class="unit unit-horizontal unit-spacing-md">
					<div class="unit-left"><span class="icon icon-primary fa-phone"></span></div>
					<div class="unit-body">
					  <h6><a href="callto:<?php echo PHONE_NUMBER_INTERNATIONAL_PREFIX.PHONE_NUMBER; ?>" class="text-darker"><?php echo PHONE_NUMBER_INTERNATIONAL_PREFIX; ?> <?php echo PHONE_NUMBER_FORMATTED; ?></a></h6>
					</div>
				  </div>
				</li>
				<li>
				  <div class="unit unit-horizontal unit-spacing-md">
					<div class="unit-left"><span class="icon icon-primary fa-envelope"></span></div>
					<div class="unit-body">
					  <h6><a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a></h6>
					</div>
				  </div>
				</li>
			  </ul>
			  <div class="offset-top-40"></div>
			</div>
			<div class="cell-sm-6 cell-lg-preffix-1 cell-lg-5 cell-xl-preffix-2 cell-xl-6">
			  <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3336.464051673264!2d-97.15466088480574!3d33.254341580830605!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x864db595e854e1a1%3A0x3a9a9ff3cc14dfe3!2sUniversity+of+North+Texas+Discovery+Park%2C+3940+N+Elm+St%2C+Denton%2C+TX+76207!5e0!3m2!1sen!2s!4v1546668215690" width="600" height="600" frameborder="0" style="border: 4px solid lightgray;" allowfullscreen></iframe>
			</div>
		  </div>
		</div>
	  </div>
	</section>
	<section class="section-50 section-md-75 section-md-100 section-lg-120 section-xl-150 bg-wild-sand">
	  <div class="shell text-left">
		<h2><span class="small">contact us</span>just fill in the contact form, and we will answer</h2>
		<form data-form-output="form-output-global" data-form-type="contact" method="post" action="/ajax/contact-us.php" class="rd-mailform text-left">
		  <div class="range offset-top-40 offset-md-top-60">
			<div class="cell-lg-4 cell-md-6">
			  <div class="form-group postfix-xl-right-40">
				<label for="contact-name" class="form-label">Name *</label>
				<input id="contact-name" type="text" name="name" data-constraints="@Required" class="form-control">
			  </div>
			  <div class="form-group postfix-xl-right-40">
				<label for="contact-email" class="form-label">E-mail *</label>
				<input id="contact-email" type="email" name="email" data-constraints="@Email @Required" class="form-control">
			  </div>
			</div>
			<div class="cell-lg-4 cell-md-6 offset-top-20 offset-md-top-0">
			  <div class="form-group postfix-xl-right-40">
				<label for="contact-company" class="form-label">Company <em>(optional)</em></label>
				<input id="contact-company" type="text" name="company" data-constraints="" class="form-control">
			  </div>
			  <div class="form-group postfix-xl-right-40">
				<label for="contact-phone" class="form-label">Phone *</label>
				<input id="contact-phone" type="text" name="phone" data-constraints="@Required" class="form-control">
			  </div>
			</div>
			<div class="cell-lg-4 offset-top-20 offset-lg-top-0">
			  <div class="form-group postfix-xl-right-40">
				<label for="contact-message" class="form-label">Message *</label>
				<textarea id="contact-message" name="message" data-constraints="@Required" class="form-control"></textarea>
			  </div>
			</div>
		  </div>
			
			<div id="contact-form-submit-area">
				<span><div class="g-recaptcha" data-sitekey="6LeWt9MUAAAAADskIvjv8Vt49_-riUjAq6O8Uihq"></div></span>
			
				<span><button type="submit" class="btn btn-form btn-default">Send message</button></span>
			</div>
			
		</form>
	  </div>
	</section>
</main>
<?php
footer();
?>
