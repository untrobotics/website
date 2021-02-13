<!DOCTYPE html>
<html lang="en" class="wide wow-animation">
  <head>
    <title><?php echo $title; ?></title>
    <meta name="format-detection" content="telephone=no">
    <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <link rel="icon" href="/favicon.png" type="image/png">
    <!-- Stylesheets-->
    <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Poppins:400,500,700%7CKanit:300,400,700">
    <link rel="stylesheet" href="/css/style.css">
		<!--[if lt IE 10]>
    <div style="background: #212121; padding: 10px 0; box-shadow: 3px 3px 5px 0 rgba(0,0,0,.3); clear: both; text-align:center; position: relative; z-index:1;"><a href="http://windows.microsoft.com/en-US/internet-explorer/"><img src="/images/ie8-panel/warning_bar_0000_us.jpg" border="0" height="42" width="820" alt="You are using an outdated browser. For a faster, safer browsing experience, upgrade for free today."></a></div>
    <script src="js/html5shiv.min.js"></script>
		<![endif]-->
      
	<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
	<script>
		(adsbygoogle = window.adsbygoogle || []).push({
		  google_ad_client: "ca-pub-8895588094075798",
		  enable_page_level_ads: true
		});
	</script>

	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=UA-134691551-1"></script>
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', 'UA-134691551-1');
	</script>
	  
     <style>
	 	.icon-md2 {
			font-size: 25px;
		}
	 </style>
     
  </head>
  <body>
    <!-- Page-->
    <div class="page text-center text-md-left">
      <div class="page-loader">
        <div>
          <div class="page-loader-body">
            <div class="cssload-loader">
              <div class="cssload-inner cssload-one"></div>
              <div class="cssload-inner cssload-two"></div>
              <div class="cssload-inner cssload-three"></div>
            </div>
          </div>
        </div>
      </div>
      <!-- Page Header-->
      <header class="page-head">
        <div class="rd-navbar-wrap">
          <nav data-stick-up-clone="true" data-layout="rd-navbar-fixed" data-md-layout="rd-navbar-static" data-md-device-layout="rd-navbar-fixed" data-lg-layout="rd-navbar-static" data-lg-device-layout="rd-navbar-static" data-lg-stick-up-offset="252px" class="rd-navbar rd-navbar-secondary">
            <div class="rd-navbar-inner">
              <ul class="list-inline list-inline-lg offset-top-0">
				<li><a href="/events" class="ioon icon-md2 icon-silver-chalice fa-calendar"></a></li>
                <li><a href="<?php echo SOCIAL_MEDIA_FACEBOOK_URL; ?>" class="ioon icon-md2 icon-silver-chalice fa-facebook"></a></li>
                <li><a href="<?php echo SOCIAL_MEDIA_TWITTER_URL; ?>" class="ioon icon-md2 icon-silver-chalice fa-twitter"></a></li>
                <li><a href="<?php echo SOCIAL_MEDIA_INSTAGRAM_URL; ?>" class="ioon icon-md2 icon-silver-chalice fa-instagram"></a></li>
              </ul>
            </div>
            <div class="rd-navbar-inner">
              <!-- RD Navbar Panel-->
              <div class="rd-navbar-panel">
                <!-- RD Navbar Toggle-->
                <button data-rd-navbar-toggle=".rd-navbar-nav-wrap" class="rd-navbar-toggle"><span></span></button>
                <button data-rd-navbar-toggle=".rd-navbar-collapse" class="rd-navbar-collapse-toggle" style="z-index: 99;"><span></span></button>
                <div class="rd-navbar-brand"><a href="/" class="brand-name"><span class="brand-logo veil">UNT Robotics</span><img class="brand-logo-main" src="/images/unt-robotics-brand-logo-white.svg" alt="UNT Robotics"><span class="brand-text-main">UNT Robotics</span></a></div>
              </div>
              <div class="rd-navbar-collapse animated">
                <div class="rd-navbar-collapse-items">
                  <ul class="list-inline text-center text-lg-left">
                    <li>
                      <div class="unit unit-horizontal unit-spacing-xs">
                        <div class="unit-left"><span class="icon icon-primary fa-phone"></span></div>
                        <div class="unit-body">
                          <div class="title"><span class="small">CALL US</span></div>
                          <h6><a href="callto:<?php echo PHONE_NUMBER; ?>"><?php echo PHONE_NUMBER_FORMATTED; ?></a></h6>
                        </div>
                      </div>
                    </li>
                    <li>
                      <div class="unit unit-horizontal unit-spacing-xs">
                        <div class="unit-left"><span class="icon icon-primary fa-map-marker"></span></div>
                        <div class="unit-body">
                          <div class="title"><span class="small">EMAIL US</span></div>
                          <h6><a href="mailto:<?php echo EMAIL_SUPPORT; ?>"><?php echo EMAIL_SUPPORT; ?></a></h6>
                        </div>
                      </div>
                    </li>
                  </ul>
                </div>
                <div class="rd-navbar-collapse-items">
                  <ul class="list-inline list-inline-lg offset-top-0">
					<li><a href="/events" class="ioon icon-sm icon-silver-chalice fa-calendar"></a></li>
                    <li><a href="<?php echo SOCIAL_MEDIA_FACEBOOK_URL; ?>" class="ioon icon-sm icon-silver-chalice fa-facebook"></a></li>
                    <li><a href="<?php echo SOCIAL_MEDIA_TWITTER_URL; ?>" class="ioon icon-sm icon-silver-chalice fa-twitter"></a></li>
                    <li><a href="<?php echo SOCIAL_MEDIA_INSTAGRAM_URL; ?>" class="ioon icon-sm icon-silver-chalice fa-instagram"></a></li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="rd-navbar-inner">
              <div class="rd-navbar-nav-wrap">
                <!-- RD Navbar Nav-->
                <ul class="rd-navbar-nav">
                  <li class="active"><a href="/">Home</a></li>
					<li><a href="/about">About Us</a>
						<ul class="rd-navbar-dropdown">
						  <li><a href="/about">What We Do</a></li>
						  <li><a href="/about/our-team">Our Team</a></li>
						  <!--<li><a href="/activities">Activities</a></li>-->
						</ul>
					</li>
                  <!--<li><a href="/about">About Us</a>
                    <ul class="rd-navbar-dropdown">
                      <li><a href="/about">What We Do</a></li>
					  <li><a href="/membership">Membership Info</a></li>
                      <li><a href="/our-team">Our Team</a></li>
                      <li><a href="/history">History</a></li>
                      <li><a href="/press">Press</a></li>
					  <li><a href="/sponsorships">Sponsorships</a></li>
                    </ul>
                  </li>
				  <li><a href="/sponsorships">Sponsor Us</a></li>
				  <li><a href="/competitions">Competitions</a>
                    <ul class="rd-navbar-dropdown">
                  	  <li><a href="/botathon">Botathon</a></li>
                      <li><a href="/competition/2019/ieee-r5-robotics">IEEE Region 5 Robotics 2019</a></li>
                    </ul>
                  </li>
                  <li><a href="/projects">Projects</a>
                    <ul class="rd-navbar-dropdown">
                      <li><a href="/project/sofabot">Sofabot</a></li>
					  <li><a href="/project/sofabot">T-Shirt Cannon Bot</a></li>
					  <li><a href="/project/sofabot">SAE Racecar Dashboard</a></li>
                    </ul>
                  </li>
                  <li><a href="/guides">Guides</a></li>
				  <li><a href="/equipment" class="stacked">Checkout<br>Equipment</a></li>-->
				  <li><a href="/dues">Pay Dues</a></li>
				  <li><a href="/merch">Merch</a>
                    <ul class="rd-navbar-dropdown">
                  	    <li><a href="/merch/shirts-hoodies">Shirts &amp; Hoodies</a></li>
                  	    <li><a href="/merch/hats">Hats</a></li>
                        <li><a href="/merch/trousers">Trousers</a></li>
                        <li><a href="/merch/gear">Gear</a></li>
                    </ul>
                  </li>
				  <li><a href="/botathon">Botathon</a>
                    <ul class="rd-navbar-dropdown">
                  	  <li><a href="/botathon">Info</a></li>
                      <li><a href="/botathon/register">Register</a></li>
					  <!--<li><a href="/botathon/brackets">Brackets</a></li>-->
                    </ul>
                  </li>
                  <li><a href="/contact">Contact us</a></li>
			<?php
			if (count($userinfo)) {
			?>
				<li class="thin auth" style="margin-left: auto;"><a href="/me/" class="stacked">My<br>Profile</a></li>
				<li class="thin auth" style="margin-left: 0;"><a href="/auth/logout" class="stacked">Log<br>Out</a></li>
			<?php } else { ?>
				<li class="thin" style="margin-left: auto;"><a href="/auth/login" class="stacked">Log<br>In</a></li>
	                	<li class="thin" style="margin-left: 0;"><a href="/auth/join" class="stacked">Join<br>Now</a></li>
			<?php
			}
			?>
                </ul>
              </div>
            </div>
          </nav>
        </div>
      </header>
      <!-- Page Content-->
