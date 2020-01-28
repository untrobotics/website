<!-- Page Footer-->
      <footer class="page-footer page-footer-default">
        <div class="shell">
          <hr class="divider divider-mine-shaft">
        </div>
        <section class="section-75 section-sm-80 section-md-100">
          <div class="shell">
            <div class="range text-sm-left">
              <div class="cell-sm-6 cell-lg-3 col-lg-offset-1"><span class="small text-spacing-340 text-white text-uppercase text-bold">UNT Robotics</span>
                <p class="offset-top-20">We are a student-run organisation at the <a href="https://www.unt.edu">University of North Texas</a>.</p>
              </div>
              <div class="cell-sm-6 cell-lg-3"><span class="small text-spacing-340 text-white text-uppercase text-bold">RECENT UPDATES</span>
                <!--<article class="event offset-top-20">
                  <p><a href="#" class="text-white">TWITTER FEED HERE #1</a></p>
                  <time datetime="2017" class="small offset-top-10">Jan 1, 2019</time>
                </article>
                <article class="event offset-top-20">
                  <p><a href="#" class="text-white">TWITTER FEED HERE #2</a></p>
                  <time datetime="2017" class="small offset-top-10">Jan 1, 2019</time>
                </article>-->
				  <!--
				  	<a class="twitter-timeline" href="https://twitter.com/UNTRobotics" data-tweet-limit="2" data-theme="dark">Tweets by @UNTRobotics</a>
					<script async src="//platform.twitter.com/widgets.js" charset="utf-8"></script>-->
				  <?php
				  require_once(BASE . '/api/twitter/get-tweets.php');
				  echo get_last_three_tweets();
				  ?>
              </div>
              <div class="cell-sm-6 cell-lg-4 cell-xl-3 offset-top-40 offset-lg-top-0"><span class="small text-spacing-340 text-white text-uppercase text-bold">CONTACT INFO</span>
                <p class="offset-top-20">You can always contact us via email or phone.</p>
                <ul class="list offset-top-20 text-left">
                  <li>
                    <div class="unit unit-horizontal unit-spacing-xs">
                      <div class="unit-left"><span class="icon icon-primary fa-map-marker"></span></div>
                      <div class="unit-body"><a href="/contact" class="text-gray-lighter">3940 N Elm St, Denton, TX 76207</a></div>
                    </div>
                  </li>
                  <li>
                    <div class="unit unit-horizontal unit-spacing-xs">
                      <div class="unit-left"><span class="icon icon-primary fa-phone"></span></div>
                      <div class="unit-body"><a href="callto:<?php echo PHONE_NUMBER_INTERNATIONAL_PREFIX.PHONE_NUMBER; ?>" class="text-gray-lighter"><?php echo PHONE_NUMBER_FORMATTED; ?></a></div>
                    </div>
                  </li>
                  <li>
                    <div class="unit unit-horizontal unit-spacing-xs">
                      <div class="unit-left"><span class="icon icon-primary fa-envelope"></span></div>
                      <div class="unit-body"><a class="text-gray-lighter" href="mailto:hello@untrobotics.com">hello@untrobotics.com</a></div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </section>
		  
		  <div class="untr-adsense-container">
			  <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
				<!-- Footer Ad -->
				<ins class="adsbygoogle"
					 style="display:block"
					 data-ad-client="ca-pub-8895588094075798"
					 data-ad-slot="9904502570"
					 data-ad-format="auto"
					 data-full-width-responsive="true"></ins>
				<script>
				(adsbygoogle = window.adsbygoogle || []).push({});
				</script>
		  </div>
		  
        <section class="section-25 bg-shark-1">
          <div class="shell">
            <p class="small-xs">Copyright 2018-<?php echo date('Y'); ?> - All rights reserved by <strong>UNT Robotics</strong>. <a href="/legal/privacy" class="text-white">Privacy Policy</a>
            </p>
          </div>
        </section>
      </footer>
    </div>

	<script src="/js/core.min.js"></script>
	<script src="/js/script.js"></script>

  </body>
</html>
