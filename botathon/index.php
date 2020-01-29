<?php
require('../template/top.php');
head('Botathon Info', true);
?>

<style>
	h2 {
		border-bottom: 1px solid lightgray;
	}
	#schedule tr td:first-of-type {
		font-weight: 800;
		padding-right: 15px;
		font-family: Consolas, "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", Monaco, "Courier New", "monospace";
		color: black;
	}
	.parts tr {
		border-bottom: 1px solid gray;
	}
	.parts tr th {
		color: black;
	}
	.parts tr td:first-of-type {
		width: 200px;
		font-weight: 800;
	}
	.parts tr td:nth-of-type(2) {
		width: 150px;
	}
	.parts.basekit tr td:nth-of-type(3) {
		width: 200px;
	}
	.parts.upgrades tr td:nth-of-type(3) {
		width: 150px;
	}
	
	.field-preview h6 > a {
		height: 100px;
		display: block;
	}
	
	table#schedule tr td:first-of-type {
		min-width: 100px;
	}
	
	.page #botathon-navigation ul li.active a {
		color: #45cd8f !important;
	}
	
	.page #botathon-navigation ul li a,
	.page #botathon-navigation ul li
	{
		transition: all 0.5s linear;
	}
	
	.sponsors {
		text-align: center;
	}
	.sponsors .sponsor {
		display: inline-block;
		margin: 0px 20px;
		padding: 10px;
	}
	
	@media (min-width: 992px) {
	  	#botathon-navigation {
			position: sticky;
			position: -webkit-sticky;
			top: 0;
			/* height: 0px; */
			float: right;
			/* right: -500px; */
			width: 300px;
			/* padding: 0px; */
			margin-right: 50px;
	  	}
		.botathon-navigation {
			padding-top: 50px;
		}
	}
	
</style>

       <main class="page-content">
        <!-- Classic Breadcrumbs-->
        <section class="breadcrumb-classic">
          <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/hackathon-demo.jpg" class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
              <div class="shell">
                <ul class="list-breadcrumb">
                  <li><a href="/">Home</a></li>
                  <li>Botathon</li>
                </ul>
              </div>
            </div>
          </div>
        </section>
		   
		<!--<section class="section-50">
			<div class="shell">
				<div class="range range-md-justify">
					<div class="cell-lg-12">
						
						<center><a href="register" class="btn btn-default btn-round" style="margin: 20px;">Register Now</a></center>
						
						<iframe src="https://www.untrobotics.com/pdfjs/web/viewer.html?file=/downloads/botathon-info-packet.pdf" width="100%" height="1000"></iframe>
					</div>
				</div>
			</div>
		</section>-->
		
		<section class="section-50" id="botathon-navigation">
          <div class="shell">
            <div class="range range-md-right">
              <div class="col-lg-12 botathon-navigation">
                <div class="well-custom-1">
                  <h4 class="text-regular">Navigation</h4>
                  <ul class="list-xs list-marked">
                    <li class="active"><a href="#info" class="text-content">What is Botathon</a></li>
					<li><a href="#spnsors" class="text-content">Event Sponsors</a></li>
                    <li><a href="#rules" class="text-content">Rules</a></li>
                    <li><a href="#schedule" class="text-content">Schedule</a></li>
                    <li><a href="#field-preview" class="text-content">Field Preview</a></li>
                    <li><a href="#parts-list" class="text-content">Parts List</a></li>
                    <li><a href="#teams" class="text-content">Teams</a></li>
					<li><a href="#tshirts" class="text-content">T-Shirts</a></li>
					<li><a href="#contacts" class="text-content">Contact Info</a></li>
                    <li><a href="#register" class="text-content">Register</a></li>
                    <li><a href="brackets" class="text-content">Gameday Brackets</a></li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </section>
		   
        <section class="section-50" id="info">
          <div class="shell">
            <div class="range range-md-justify">
              <div class="cell-md-12 cell-lg-10">
                <div class="inset-md-right-30 inset-lg-right-0">
					<h1><strong>Botathon</strong> - <em>Season 1</em></h1>
					<p>Botathon is UNT’s first ever hosted robotics competition, think hackathon+robots!</p>
                  
                  	<p>Participants will have 8 hours to build a robot then compete head to head in a race to pop the most balloons in our custom built arena. This event is open to all UNT students, with any amount of experience.</p>
                  
                  	<div class="well-custom">
                    	<h5>Open to all UNT students, learn something new and make new friends.</h5>
                  	</div>
                  
					<p>The goal of this event is to give students a chance to learn how to build a robot and compete against their peers. Botathon is free to all UNT students and will feature free food, workshops, tech demos, and prizes!</p>
					
					<p style="margin-top:40px; font-size: 22px;"><strong>When/Where is it happening?</strong></p>
					<h4 style="margin-top: 10px;"><em>10am-10pm</em>, <strong>April 27th, 2019</strong></h4>
					<h3>@ <strong>UNT Discovery Park</strong></h3>
					
					<p>Our official informational packet is available <a href="/downloads/botathon-info-packet.pdf">here</a>.</p>
               
                </div>
              </div>
            </div>
          </div>
        </section>
		
		<section class="section-50" id="sponsor">
          <div class="shell">
			<h2>Event Sponsors</h2>
            <div class="range">
			 	<div class="cell-md-8 cell-lg-9">
                	<div class="sponsors">
						<div class="sponsor"><img src="/images/sponsor-logos/botathon/monster-energy.jpg"/></div>
						<div class="sponsor"><img src="/images/sponsor-logos/botathon/marcos-pizza.jpg"/></div>
						<div class="sponsor"><img src="/images/sponsor-logos/botathon/eagles-nest.jpg"/></div>
						<div class="sponsor"><img src="/images/sponsor-logos/botathon/elegoo.jpg"/></div>
					</div>
				</div>
			</div>
          </div>
        </section>
		   
        <section class="section-50" id="rules">
          <div class="shell">
			<h2>Rules</h2>
            <div class="range">
			 	<div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0">
						<p>Teams of up to four students will be given two robot kits <em>(consult the parts list below to see what these contain)</em>, and 8 hours in which you may build as many robots as you want.</p>
						<p><strong>The challenge:</strong> to use your team’s robots to pop the other team’s balloons, while defending your own.</p>					<p>Each side of the field contains 13 balloons, and each team must place 4 balloons on their robot(s). The objective is to pop as many of the opposite team’s balloons within 4 minutes, the team with the most points when the time runs out, wins.</p>
						<p>Of the 13 balloons on each side, eight will be placed at ground level and five will be placed on the elevated ramps in the middle of the arena. There is one point awarded for each ground-level balloon popped, two points for each heightened balloon and three points for each balloon that is on a robot. If a team can pop all of 17 of the opponent's balloons, they earn a five-point bonus. This works out to a maximum of 35 points in a single round.</p>
						<p>For information about contact and penalties, please consult the <a href="/downloads/botathon-info-packet.pdf">information packet</a>.</p>
					</div>
				</div>
			</div>
          </div>
        </section>
		   
		<section class="section-50" id="schedule">
          <div class="shell">
			<h2>Schedule</h2>
            <div class="range">
			 	<div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0">
						<table id="schedule">
							<tr><td>&nbsp;7:30 am</td><td>Volunteer Call time</td></tr>
							<tr><td>&nbsp;9:00 am</td><td>Check-in begins</td></tr>
							<tr><td>10:00 am</td><td>Competition Rules and Event Overview</td></tr>
							<tr><td>10:30 am</td><td>Development/Part Check-Out period begins</td></tr>
							<tr><td>10:30 am</td><td>Workshop: Crash Course in Robotics and Team Building</td></tr>
							<tr><td>12:00 pm</td><td>Lunch</td></tr>
							<tr><td>&nbsp;1:00 pm</td><td>Demo: IEEE R5 Autonomous robot demo and Q/A</td></tr>
							<tr><td>&nbsp;3:00 pm</td><td>Resume / Career Workshop</td></tr>
							<tr><td>&nbsp;6:00 pm</td><td>Dinner</td></tr>
							<tr><td>&nbsp;6:30 pm</td><td>Competition Begins</td></tr>
							<tr><td>&nbsp;9:00 pm</td><td>Finals</td></tr>
							<tr><td>&nbsp;9:30 pm</td><td>Winners announced and prizes distributed</td></tr>
						</table>
					</div>
				</div>
			</div>
          </div>
        </section>
        
		<section class="section-50" id="field-preview">
			<div class="shell">
			  <h2>Field preview</h2>
			  <div class="range">
				  <div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0 field-preview">
						<h5 style="padding: 10px;">The arena is 30 ft. x 12 ft.</h5>
						<div class="col-md-6 col-sm-12"><img src="/images/botathon-photos/field-1.jpg" alt="" width="485" height="555" class="img-responsive"/>
						  <h6><a href="/images/botathon-photos/field-1.jpg">The botathon competition field center obstacle</a></h6>
						</div>
						<div class="col-md-6 col-sm-12 offset-top-40 offset-sm-top-0"><img src="/images/botathon-photos/robots-square.jpg" alt="" width="485" height="555" class="img-responsive"/>
						  <h6><a href="/images/botathon-photos/robots-square.jpg">Demo robots squaring off</a></h6>
						</div>
						<div class="col-md-6 col-sm-12 offset-top-40 offset-md-top-0"><img src="/images/botathon-photos/field-2.jpg" alt="" width="485" height="555" class="img-responsive"/>
						  <h6><a href="/images/botathon-photos/field-2.jpg">The field will contain baloons for each team to pop</a></h6>
						</div>
						
						<div class="col-md-6 col-sm-12 offset-top-40 offset-md-top-0"><img src="/images/botathon-photos/cad-1.jpg" alt="" width="485" height="555" class="img-responsive"/>
						  <h6><a href="/images/botathon-photos/cad-1.jpg">CAD of the standing view of the field</a></h6>
						</div>
						<div class="col-md-6 col-sm-12 offset-top-40 offset-md-top-0"><img src="/images/botathon-photos/cad-2.jpg" alt="" width="485" height="555" class="img-responsive"/>
						  <h6><a href="/images/botathon-photos/cad-2.jpg">3D CAD of the field</a></h6>
						</div>
						<div class="col-md-6 col-sm-12 offset-top-40 offset-md-top-0"><img src="/images/botathon-photos/cad-3.jpg" alt="" width="485" height="555" class="img-responsive"/>
						  <h6><a href="/images/botathon-photos/cad-3.jpg">Birds eye CAD of the field</a></h6>
						</div>
					  </div>
				  </div>
			  </div>
			</div>
		</section>
		   
		<section class="section-50" id="parts-list">
          <div class="shell">
			<h2>Parts List</h2>
            <div class="range">
			 	<div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0">
						
						<h4>Base Kit</h4>
						<p>Each team gets two base kits, which consist of the below items.</p>
						
						<table class="parts basekit">
							<tr><th>Part Name</th><th>Quantity</th></tr>
							<tr><td>Motor</td><td>2</td></tr>
							<tr><td>Wheels</td><td>2</td></tr>
							<tr><td>Arduino</td><td>2</td></tr>
							<tr><td>2WD Chassis</td><td>1</td></tr>
							<tr><td>Caster Wheel</td><td>1</td></tr>
							<tr><td>Battery Harness</td><td>1</td></tr>
							<tr><td>Motor Controller</td><td>1</td></tr>
							<tr><td>AA Battery</td><td>4</td></tr>
							<tr><td>Motor</td><td>2</td></tr>
							<tr><td>Power Switch</td><td>1</td></tr>
							<tr><td>BLE Module</td><td>1</td></tr>
							<tr><td>Motor</td><td>2</td></tr>
						</table>
						
						
						<h4 style="margin-top: 40px;">Upgrades</h4>
						<p>These parts are able to be bought (using in-game currency) and will be in addition to the base kits parts that you are given freely. Each team is given $500 of in-game currency.</p>
						<table class="parts upgrades">
							<tr><th>Part Name</th><th>Quantity</th><th>Price (game currency)</th></tr>
							<tr><td>Arduino</td><td>1</td><td>$150</td></tr>
							<tr><td>Raspberry Pi</td><td>1</td><td>$300</td></tr>
							<tr><td>Servo</td><td>1</td><td>$50</td></tr>
							<tr><td>Metal Gear Servo</td><td>1</td><td>$100</td></tr>
							<tr><td>Arduino Servo Shield</td><td>1</td><td>$100</td></tr>
							<tr><td>Motor/Wheel</td><td>1</td><td>$50</td></tr>
							<tr><td>4WD Chassis</td><td>1</td><td>$100</td></tr>
							<tr><td>Buiding Materials</td><td>1</td><td>TBD</td></tr>
							<tr><td>Battery Harness</td><td>1</td><td>$50</td></tr>
							<tr><td>AA Battery</td><td>4</td><td>$50</td></tr>
							<tr><td>LED</td><td>1</td><td>$10</td></tr>
							<tr><td>Ultrasonic Sensor</td><td>1</td><td>$75</td></tr>
							<tr><td>3D Printing 30 mins</td><td>unlimited</td><td>$75</td></tr>
							<tr><td>3D Printing 1 hour (prior to event)</td><td>unlimited</td><td>$30</td></tr>
							<tr><td>Misc. screws &amp; bolts</td><td>unknown</td><td>FREE</td></tr>
							<tr><td>Jumper cables</td><td>unknown</td><td>FREE</td></tr>
							<tr><td>Mini breadboards</td><td>unknown</td><td>FREE</td></tr>
						</table>
					</div>
				</div>
			</div>
          </div>
        </section>
		   
		<section class="section-50" id="teams">
          <div class="shell">
			<h2>Teams</h2>
            <div class="range">
			 	<div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0">
						<p>Teams will consist of 4 members. Each team will be given 2 base kits and $500 of in-game currency to buy upgrade parts. Participants are encouraged to form their team prior to the event, but there will be team making opportunities on the day for individuals without a team.</p>
						<p>There will be mentors to assist teams during the build process, but mentors will not be assigned to a specific team.</p>
					</div>
				</div>
			</div>
          </div>
        </section>
		
		<section class="section-50" id="tshirts">
          <div class="shell">
			<h2>T-Shirts</h2>
            <div class="range">
			 	<div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0">
						<p>You may order official UNT Robotics T-shirts to wear at the event, <a href="/merch/shirts">click here to order</a>.</p>
					</div>
				</div>
			</div>
          </div>
        </section>
		   
		<section class="section-50" id="contacts">
          <div class="shell">
			<h2>Contact Info</h2>
            <div class="range">
			 	<div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0">
						<p>If you have any questions at all, please get in touch with us at <a href="mailto:<?php echo EMAIL_SUPPORT; ?>"><?php echo EMAIL_SUPPORT; ?></a> or call us at <a href="tel:<?php echo PHONE_NUMBER; ?>"><?php echo PHONE_NUMBER_FORMATTED; ?></a>.</p>
						<p>We're always happy to help, no matter how small or big your question is.</p>
					</div>
				</div>
			</div>
          </div>
        </section>
		   
	    <section class="section-50" id="register">
			<div class="shell">
			  <h2>Register Now</h2>
			  <div class="range">
				  <div class="cell-md-8 cell-lg-9">
                	<div class="inset-md-right-30 inset-lg-right-0">
						<center><a href="register" class="btn btn-default btn-round" style="margin: 20px;">Register Now</a></center>
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
	$("#botathon-navigation ul li a").click(function(e) {
		$("#botathon-navigation ul li.active").removeClass("active");
		$(this).parent().addClass("active");
	});
	
	$(document).ready(function(){
	  // Add smooth scrolling to all links
	  $("#botathon-navigation ul li a").on('click', function(event) {

		// Make sure this.hash has a value before overriding default behavior
		if (this.hash !== "") {
		  // Prevent default anchor click behavior
		  event.preventDefault();

		  // Store hash
		  var hash = this.hash;

		  // Using jQuery's animate() method to add smooth page scroll
		  // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
		  $('html, body').animate({
			scrollTop: $(hash).offset().top
		  }, 800, function(){

			// Add hash (#) to URL when done scrolling (default click behavior)
			window.location.hash = hash;
		  });
		} // End if
	  });
	});
</script>