<?php
require('template/top.php');
head('Home', true);
?>
<style>
	.promo-video {
		width: 100%;
		position: absolute;
		top: 0px;
		left: 0px;
	}
	@media (max-width: 992px) {
	  	.promo-video {
			height: 100%;
			width: auto;
	  	}
	}
	.sponsors {
		text-align: center;
	}
	.sponsors .sponsor {
		display: inline-block;
		margin: 0px 20px;
		padding: 10px;
	}
</style>
      <main class="page-content hide-overflow">
        <div style="height: 600px !important" data-min-height="300px" class="swiper-container swiper-slider swiper-secondary">
          <div class="swiper-wrapper text-md-left">
            <div class="swiper-slide">
              <div class="swiper-slide-caption">
                <div class="shell">
                  <div class="slider-padding range section-100-vh range-xs-middle range-xs-center range-md-right">
                    <div data-caption-animate="fadeInDown" data-caption-delay="200" class="cell-md-6 cell-sm-9 postfix-md-right-90 postfix-xl-right-0">
                      <h1>Get Involved</h1>
                      <div class="divider-1"></div>
                      <p class="h6 offset-top-40">Tap one of the buttons below to officially become part of UNT Robotics</p>
			<div class="group">
				<a class="join-btn discord" href="/join/discord"><img src="/images/btn-discord.png"/></a>
				<a class="join-btn campuslabs" href="/join/campuslabs"><img src="/images/btn-campuslabs.png"/></a>
			</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="swiper-slide">
              <div class="swiper-slide-caption">
                <div class="shell">
                  <div class="slider-padding range section-100-vh range-xs-middle range-xs-center range-md-right">
                    <div data-caption-animate="fadeInDown" data-caption-delay="200" class="cell-md-6 cell-sm-9 postfix-md-right-90 postfix-xl-right-0">
                      <h1>Building our futures</h1>
                      <div class="divider-1"></div>
                      <p class="h6 offset-top-40">We develop our community, our skills and epic robots</p>
                      <div class="group">
						  <a href="/about" class="btn btn-patina btn-form">Learn more about what we do</a>
					  </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="swiper-button-prev"><span>Prev</span></div>
          <div class="swiper-button-next"><span>Next</span></div>
          <div data-custom-scroll-to="custom-way-point" class="custom-way-point custom-way-point-swiper animated">Scroll down</div>
			<video class="promo-video" autoplay="" muted="" loop="">
			  <source src="/media/home-video-feature.mp4" type="video/mp4">
			</video>
        </div>

        <section id="custom-way-point" class="section-75 section-md-120 section-lg-120 section-xl-160 section-xl-bottom-180" style="background-color: whitesmoke;">
          <div class="shell text-left">
            <div class="range">
              <div class="cell-sm-6 section-sm-40 section-lg-80 section-lg-bottom-110">
                <div class="divider-block">
                  <h2>Changing the world, one student at a time</h2>
                  <h6>Since our humble beginnings as a group of UNT students yearning for some circuit boards and wheels to jerry-rig into a new friend, we have made a name for ourselves by growing into a large student organisation</h6>
                  <p>Our mission is to inspire and teach people the skills to achieve their goals in robotics. We provide our members with the opportunities to learn, create and grow. Through hands-on workshops, an open workspace and competitions, we aim to give our members the educational and career opportunities outside of what engineering students obtain in the classroom. We welcome people of all skill levels.</p>
                </div>
              </div>
              <div class="cell-sm-5 cell-sm-preffix-1 bg-custom-image bg-custom-image-right bg-custom-image-3">
                <div class="custom-border custom-border-whitesmoke"></div>
              </div>
            </div>
          </div>
        </section>

		  <section class="section-75" style="border-top: 2px solid lightgray; border-bottom: 2px solid lightgray;">
			  <div class="shell">
				<div class="range">
				  <div>
					<div class="divider-block">
					  <h2>Our Sponsors</h2>
					  	<div class="sponsors">
							<div class="sponsor"><img src="/images/sponsor-logos/respec.jpg"/></div>
							<div class="sponsor"><img src="/images/sponsor-logos/servocity.jpg"/></div>
							<div class="sponsor"><img src="/images/sponsor-logos/ieee-ft-worth.jpg"/></div>
							<div class="sponsor"><img src="/images/sponsor-logos/eagles-nest.jpg"/></div>
						</div>
					</div>
				  </div>
				</div>
			  </div>
			</section>

        <section class="section-75 section-sm-80 section-md-120 section-xl-150 section-xl-top-130 contact-darker bg-image bg-image-1">
          <div class="shell">
            <div class="range">
              <div class="cell-md-10 cell-lg-7 cell-xl-6">
                <div class="divider-block">
                  <h2>Get Involved</h2>
                </div>
              </div>
            </div>
            <div class="range">
              <div class="cell-sm-6 cell-lg-4 cell-xl-3 cell-xl-preffix-1"><span class="icon icon-lg icon-primary thin-icon-lightbulb"></span>
                <h5 class="offset-top-15 offset-lg-top-25"><a href="/guides"> Learn</a></h5>
                <p class="text-gray-light-1 text-justify">Check out our guides for getting started with the components we commonly use in robotics.</p>
              </div>
              <div class="cell-sm-6 cell-lg-4 cell-xl-3 offset-top-50 offset-sm-top-0 cell-xl-preffix-1"><span class="icon icon-lg icon-primary thin-icon-study"></span>
                <h5 class="offset-top-15 offset-lg-top-25"><a href="/workshops" class="postfix-xl-right--25">Workshops</a></h5>
                <p class="text-gray-light-1 text-justify">Come learn a valuable new skill, such as CAD or microcontroller programming.</p>
              </div>
              <div class="cell-sm-6 cell-lg-4 cell-xl-3 offset-top-50 offset-lg-top-0 cell-xl-preffix-1"><span class="icon icon-lg icon-primary thin-icon-pointer"></span>
                <h5 class="offset-top-15 offset-lg-top-25"><a href="https://www.github.com/UNTRobotics">Codebase</a></h5>
                <p class="text-gray-light-1 text-justify">Access our GitHub full of pre-written libraries and cool projects that we've compiled over the years.</p>
              </div>
              <div class="cell-sm-6 cell-lg-4 cell-xl-3 offset-top-50 cell-xl-preffix-1"><span class="icon icon-lg icon-primary thin-icon-box"></span>
                <h5 class="offset-top-15 offset-lg-top-25"><a href="/botathon"> Botathon</a></h5>
                <p class="text-gray-light-1 text-justify">Our starter competition for newcomers to the field to get hands on with our annual competition.</p>
              </div>
              <div class="cell-sm-6 cell-lg-4 cell-xl-3 offset-top-50 cell-xl-preffix-1"><span class="icon icon-lg icon-primary thin-icon-car"></span>
                <h5 class="offset-top-15 offset-lg-top-25"><a href="/projects">Projects</a></h5>
                <p class="text-gray-light-1 text-justify">Get involved in our recreational robotics projects, everything from a self-driving sofa to a racecar dashboard.</p>
              </div>
              <div class="cell-sm-6 cell-lg-4 cell-xl-3 offset-top-50 cell-xl-preffix-1"><span class="icon icon-lg icon-primary thin-icon-chart"></span>
                <h5 class="offset-top-15 offset-lg-top-25"><a href="/competitions">Compete</a></h5>
                <p class="text-gray-light-1 text-justify">Finding all of this too easy? Dive into the nitty-gritty by competing in inter-collegiate robotics with us.</p>
              </div>
            </div>
          </div>
        </section>
        <!-- Our projects-->
        <!--<section class="section-top-50 section-lg-0">
          <div class="shell">
            <div class="range">
              <div class="cell-md-10 cell-lg-7 cell-xl-6">
                <div class="divider-block">
                  <h2>Featured Projects</h2>
                  <h6>We have made all kinds of robots to complete all sorts of tasks<br class="veil reveal-md-block">check out some of our favourites below.
                  </h6>
                </div>
              </div>
            </div>
          </div>
          <div class="owl-carousel-wrap text-center wrap-fluid">
            <div data-items="1" data-sm-items="2" data-md-items="3" data-lg-items="4" data-xl-items="5" data-stage-padding="0" data-loop="true" data-margin="15" data-mouse-drag="true" data-autoplay="false" data-dots="true" data-nav-custom=".owl-custom-navigation" class="owl-carousel">
              <div class="owl-item">
                <div class="product product-custom"><img src="images/.." alt="" width="372" height="500" class="img-responsive"><a href="#">
                    <div class="product-content">
                      <div class="small">Sofabot</div>
                    </div></a></div>
              </div>
              <div class="owl-item">
                <div class="product product-custom"><img src="images/..." alt="" width="372" height="500" class="img-responsive"><a href="#">
                    <div class="product-content">
                      <div class="small">T-Shirt Cannon Bot</div>
                    </div></a></div>
              </div>
              <div class="owl-item">
                <div class="product product-custom"><img src="images/..." alt="" width="372" height="500" class="img-responsive"><a href="#">
                    <div class="product-content">
                      <div class="small">Botathon 2019</div>
                    </div></a></div>
              </div>
              <div class="owl-item">
                <div class="product product-custom"><img src="images/..." alt="" width="372" height="500" class="img-responsive"><a href="#">
                    <div class="product-content">
                      <div class="small">IEEE R5 2019</div>
                    </div></a></div>
              </div>
              <div class="owl-item">
                <div class="product product-custom"><img src="images/..." alt="" width="372" height="500" class="img-responsive"><a href="#">
                    <div class="product-content">
                      <div class="small">SAE Dashboard</div>
                    </div></a></div>
              </div>
			  <div class="owl-item">
                <div class="product product-custom"><img src="images/..." alt="" width="372" height="500" class="img-responsive"><a href="#">
                    <div class="product-content">
                      <div class="small">Quadcopter Workshop</div>
                    </div></a></div>
              </div>
            </div>
            <div class="owl-custom-navigation">
              <div class="owl-nav">
                <div data-owl-prev class="owl-prev">Prev</div>
                <div data-owl-next class="owl-next">Next</div>
              </div>
            </div>
          </div>
        </section>-->
      </main>

		<footer class="page-footer page-footer-default">
			<section class="section-75 section-sm-80 section-md-100 section-lg-top-140" style="border-top: 2px solid lightgray; border-bottom: 2px solid lightgray;">
			  <div class="shell">
				<div class="range">
				  <div class="cell-md-6">
					<div class="divider-block">
					  <h2>Newsletter Sign Up</h2>
					  <h6>Enter your email address to stay up to date with our events and workshops!</h6>
					</div>
				  </div>
				  <div class="cell-md-6 cell-xl-5 cell-xl-preffix-1 cell-md-bottom">
					<form data-form-output="form-output-global" data-form-type="subscribe" method="post" action="/ajax/newsletter-signup" class="rd-mailform text-left rd-mailform-subscribe">
					  <div class="form-group">
						<label for="footer-subscribe-email" class="form-label form-label-subscribe">Enter your e-mail</label>
						<input id="footer-subscribe-email" type="email" name="email" data-constraints="@Email @Required" class="form-control form-control-subscribe">
					  </div>
					  <button type="submit" class="btn btn-default btn-default-white">subscribe</button>
					</form>
				  </div>
				</div>
			  </div>
			</section>
		</footer>

<?php
footer();
?>
