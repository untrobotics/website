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
        white-space: nowrap;
    }

    .parts tr {
        background-color: #e1e1e1;
        border-top: 2px solid white;
        border-radius: 5px;
    }

    .parts tr td, .parts tr th {
        padding: 5px;
    }

    .parts tr th {
        color: black;
    }

    .parts tr td:first-of-type {
        width: 150px;
        font-weight: 800;
    }

    .parts tr td:nth-of-type(2) {
        width: 150px;
    }

    .parts.basekit tr td:nth-of-type(3) {
        width: 250px;
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
    .page #botathon-navigation ul li {
        transition: all 0.5s linear;
    }

    .sponsors {
        text-align: center;
    }

    .sponsors .sponsor {
        display: inline-block;
        margin: 0 20px;
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

    .robot-kit-row {
    }
    .robot-kit-entry > div {
        height: 100%;
        border: 1px dotted black;
        margin: 2px;
    }
    .robot-kit-entry h4 {
        text-align: center;
    }
    .robot-kit-list li {
        background-color: #e1e1e1;
        padding: 5px;
        margin-bottom: 4px;
    }

</style>

<main class="page-content">
    <!-- Classic Breadcrumbs-->
    <section class="breadcrumb-classic">
        <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/hackathon-demo.jpg"
                 class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html"
                 class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
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
                            <li class="active" id="nav-info"><a href="#info" class="text-content">What is Botathon</a></li>
                            <li id="nav-register"><a href="#register" class="text-content">Register</a></li>
                            <!--<li><a href="#rules" class="text-content">Rules</a></li>-->
                            <li id="nav-schedule"><a href="#schedule" class="text-content">Schedule</a></li>
                            <!--<li><a href="#field-preview" class="text-content">Field Preview</a></li>-->
                            <li id="nav-parts-list"><a href="#parts-list" class="text-content">Parts List</a></li>
                            <li id="nav-teams"><a href="#teams" class="text-content">Teams</a></li>
                            <!--<li><a href="#tshirts" class="text-content">T-Shirts</a></li>-->
                            <li id="nav-sponsors"><a href="#sponsors" class="text-content">Event Sponsors</a></li>
                            <li id="nav-contacts"><a href="#contacts" class="text-content">Contact Info</a></li>
                            <!--<li><a href="brackets" class="text-content">Gameday Brackets</a></li>-->
                        </ul>
                    </div>
                </div>
            </div>
    </section>

    <section class="section-50" id="info">
        <div class="shell">
            <div class="range range-md-justify">
                <div class="cell-md-12 cell-lg-10">
                    <div class="inset-md-right-30 inset-lg-right-0">
                        <h1><strong>Botathon</strong> - <em>Season <?php echo BOTATHON_SEASON?> (2024)</em></h1>

                        <h2>What is Botathon?</h2>

                        <p>Botathon is an annual event hosted by UNT Robotics where all UNT students are invited to compete in a one-day design,
                            test, build, and compete marathon!<p>

                        <p>Stay tuned for this year's theme!</p>

                        <p>
                            We provide everything you will need on the day, including parts, kits, tools, guides, mentorship,
                            and fantastic food (lunch, dinner, and snacks) for a packed day of robot building and competing. The event is open to all students,
                            regardless of skill level. Whether you're an entry-level student looking to get involved
							with and learn about robotics or an advanced robot technician, there's plenty of fun for everyone!
                        </p>

                        <p>Botathon is all about teamwork and friendly competition. Our mission is to build knowledge and skills while promoting
                            creativity and camaraderie among all participants.</p>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="section-50" id="register">
        <div class="shell">
            <div class="range range-md-justify">
                <div class="cell-md-12 cell-lg-10">
                    <div class="inset-md-right-30 inset-lg-right-0">
                        <h2>Registration</h2>
                        <p>Registration for Season  <?php echo BOTATHON_SEASON?><em>is open now</em> for all currently enrolled UNT students.</p>

                        <div class="cell-md-8 cell-lg-9">
                            <h4><strong>Mar. 8:</strong> Registration Opened</h4>
                            <h4><strong>Mar. 22:</strong> Registration Ends</h4>
                            <h4><strong>Mar. 30:</strong> Day of Event</h4>
                        </div>

                        <div class="well-custom">
                            <div><h5><strong>Open to all UNT students.</strong></h5></div>
                            <div><h5>Learn something new and make new friends!</h5></div>
                            <div class="text-center"><a href="register" class="btn btn-default btn-round"
                                                        style="margin: 20px;">Register Now</a></div>
                        </div>

                        <div class="inset-md-right-30 inset-lg-right-0">
                        </div>
                    </div>
                </div>
            </div>
    </section>

    <!--<section class="section-50" id="event-details">
        <div class="shell">
            <h2>Event Details</h2>
            <div class="range">
                <div class="cell-md-8 cell-lg-9">
                    <div>Registration</div>
                    <h4><strong>Feb. 19:</strong> Registration Opened</h4>
                    <h4><strong>Mar. 20:</strong> Registration Ends</h4>
                </div>
            </div>
        </div>
    </section>-->

    <!--<section class="section-50" id="rules">
        <div class="shell">
            <h2>Rules</h2>
            <div class="range">
                <div class="cell-md-8 cell-lg-9">
                    <div class="inset-md-right-30 inset-lg-right-0">
                        <p>Teams of up to four students will be given a robot kit <em>(consult the parts list below to see what these contain)</em>, and 2 weeks in which they may build a robot ship. The robot ship will then be submitted for inspection where UNT Robotics will hold it until competition day.</p>
                        <p><strong>The challenge: </strong>Collaborate with an opposing pirate crew to fire cannons and defeat The Kraken as quickly as possible, gaining access to The Treasure Room. From there it's an all out race against the opposing crew to collect the most treasure.</p>
                        <p>Stage 1 consists of The Kraken room. It contains The Kraken, and 3 elevated platforms with cannons on them. The objective is to work together with the opposing pirate crew to fire all 3 of the cannons, effectively disabling the Kraken and unlocking The Treasure Room. Pirate crews will be given the same score in this stage, which depends on the time taken to take down The Kraken.</p>
                        <p>The second stage consists of The Treasure Room. This room is randomly filled with an assortment of treasure, with the highest quality treasure being on raised platforms. The objective in this stage is to push treasure into your Coffer located on one side of the field. Points will be based on both the quantity and quality of treasure collected. </p>
                        <p>For information about contact and penalties, please consult the <a href="/downloads/botathon/botathon-info-packet-s2.pdf">information packet</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
-->

    <section class="section-50" id="schedule">
        <div class="shell">
            <h2>Schedule</h2>
            <div class="range">
                <div class="cell-md-8 cell-lg-9">
                    <div class="inset-md-right-30 inset-lg-right-0">
                        <div>Event Date: <strong>April 16, 2022</strong></div>
                        <table id="schedule">
                            <tr><td>&nbsp;9:00 am</td><td>Check In Opens</td></tr>
                            <tr><td>&nbsp;10:00 am</td><td>Team Finding Event</td></tr>
                            <tr><td>&nbsp;10:30 am</td><td>Build Time!</td></tr>
                            <tr><td>&nbsp;1:00 pm</td><td>Lunch</td></tr>
                            <tr><td>&nbsp;5:00 pm</td><td>Competition Begins</td></tr>
                            <tr><td>&nbsp;6:30 pm</td><td>Dinner</td></tr>
                            <tr><td>&nbsp;9:00 pm</td><td>Closing Ceremony & Winners Announced</td></tr>
                        </table>
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

                        <h4>Base Kits</h4>
                        <p>Each team will get to choose which base kit they want to use.</p>

                        <div class="row offset-top-20 robot-kit-row">

                            <div class="col-lg-4 col-sm-12 robot-kit-entry">
                                <div>
                                    <h4>MECANUM WHEEL CAR ROBOT</h4>

                                    <div class="botPics">
                                        <img src="/images/botathon-photos/mecanum-wheeled-robot.jpg" alt="Mecanum wheeled robot" class="img-responsive"/>
                                    </div>

                                    <ul class="robot-kit-list">
                                        <li>
                                            Arduino UNO Microcontroller x1
                                        </li>
                                        <li>
                                            L293D/L289N Motor Driver x1 or x2
                                        </li>
                                        <li>
                                            7.2V 2200mAh Ni-MH Battery Pack x1
                                        </li>
                                        <li>
                                            ESP32 Communication Module x1
                                        </li>
                                        <li>
                                            DC Motor x4
                                        </li>
                                        <li>
                                            Wheels x4
                                        </li>
                                        <li>
                                            Single-layer Chassis x1
                                        </li>
                                        <li>
                                            Speed Encoder Disk x4
                                        </li>
                                        <li>
                                            Nuts & Bolts (Assorted)
                                        </li>
                                        <li>
                                            Wires (Assorted)
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-12 robot-kit-entry">
                                <div>
                                    <h4>RUBBER WHEEL CAR ROBOT</h4>

                                    <div class="botPics">
                                        <img src="/images/botathon-photos/traditionally-wheeled-robot.jpg" alt="Traditionally wheeled robot" class="img-responsive"/>
                                    </div>

                                    <ul class="robot-kit-list">
                                        <li>
                                            Arduino UNO Microcontroller x1
                                        </li>
                                        <li>
                                            L293D/L289N Motor Driver x1 or x2
                                        </li>
                                        <li>
                                            7.2V 2200mAh Ni-MH Battery Pack x1
                                        </li>
                                        <li>
                                            ESP32 Communication Module x1
                                        </li>
                                        <li>
                                            DC Motor x4
                                        </li>
                                        <li>
                                            Wheels x4
                                        </li>
                                        <li>
                                            Dual-layer Chassis x2
                                        </li>
                                        <li>
                                            Chassis Connectors x6
                                        </li>
                                        <li>
                                            Speed Encoder Disk x4
                                        </li>
                                        <li>
                                            Nuts & Bolts (Assorted)
                                        </li>
                                        <li>
                                            Wires (Assorted)
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <div class="col-lg-4 col-sm-12 robot-kit-entry">
                                <div>
                                    <h4>TANK TRACK ROBOT</h4>

                                    <div class="botPics">
                                        <img src="/images/botathon-photos/tank-tracked-robot.jpg" alt="Tank track robot" class="img-responsive"/>
                                    </div>

                                    <ul class="robot-kit-list">
                                        <li>
                                            Arduino UNO Microcontroller x1
                                        </li>
                                        <li>
                                            L293D/L289N Motor Driver x1 or x2
                                        </li>
                                        <li>
                                            7.2V 2200mAh Ni-MH Battery Pack x1
                                        </li>
                                        <li>
                                            ESP32 Communication Module x1
                                        </li>
                                        <li>
                                            DC Motor x4
                                        </li>
                                        <li>
                                            Tank tracks x2
                                        </li>
                                        <li>
                                            Single-layer Chassis x1
                                        </li>
                                        <li>
                                            Nuts & Bolts (Assorted)
                                        </li>
                                        <li>
                                            Wires (Assorted)
                                        </li>
                                    </ul>
                                </div>
                            </div>

                        </div>


                        <h4 style="margin-top: 40px;">Upgrades</h4>
                        <p>You can buy these parts with botbucks (in-game currency) and will be in addition
                            to the base kits parts that you're given freely. Each team is given 500 botbucks.</p>
                        <table class="parts upgrades">
                            <tr>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Price (game currency)</th>
                            </tr>
                            <tr>
                                <td>Arduino</td>
                                <td>1</td>
                                <td>$50</td>
                            </tr>
                            <tr>
                                <td>Raspberry Pi</td>
                                <td>1</td>
                                <td>$300</td>
                            </tr>
                            <tr>
                                <td>Servo</td>
                                <td>1</td>
                                <td>$50</td>
                            </tr>
                            <tr>
                                <td>Metal Gear Servo</td>
                                <td>1</td>
                                <td>$100</td>
                            </tr>
                            <tr>
                                <td>Arduino Servo Shield</td>
                                <td>1</td>
                                <td>$100</td>
                            </tr>
                            <tr>
                                <td>Motor/Wheel</td>
                                <td>1</td>
                                <td>$50</td>
                            </tr>
                            <tr>
                                <td>4WD Chassis</td>
                                <td>1</td>
                                <td>$100</td>
                            </tr>
                            <tr>
                                <td>Buiding Materials (cardboard, plastic, etc.)</td>
                                <td>1</td>
                                <td>FREE</td>
                            </tr>
                            <tr>
                                <td>Battery Harness</td>
                                <td>1</td>
                                <td>$50</td>
                            </tr>
                            <tr>
                                <td>LED</td>
                                <td>1</td>
                                <td>$10</td>
                            </tr>
                            <tr>
                                <td>Ultrasonic Distnace Sensor</td>
                                <td>1</td>
                                <td>$75</td>
                            </tr>
                            <tr>
                                <td>Misc. screws &amp; bolts</td>
                                <td>unknown</td>
                                <td>FREE</td>
                            </tr>
                            <tr>
                                <td>Jumper cables</td>
                                <td>10</td>
                                <td>FREE</td>
                            </tr>
                            <tr>
                                <td>Mini breadboards</td>
                                <td>1</td>
                                <td>FREE</td>
                            </tr>
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
                        <p>Teams will consist of 4-5 members. Each team will be given a base kit and 500
                            botbucks of in-game currency to buy upgrade parts for their robot. Participants are
                            encouraged to form their crew prior to registration, but a team building event will
                            be held on the morning of the event to help find teammates.</p>
                        <p>There will be mentors to assist teams during the build process in our <a href="/join/discord">Discord</a>, as well as in person. </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--<section class="section-50" id="tshirts">
        <div class="shell">
            <h2>T-Shirts</h2>
            <div class="range">
                <div class="cell-md-8 cell-lg-9">
                    <div class="inset-md-right-30 inset-lg-right-0">
                        <p>You may order official UNT Robotics T-shirts to wear at the event, <a
                                    href="/merch/shirts-hoodies">click here to order</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>-->

    <section class="section-50" id="sponsors">
        <div class="shell">
            <h2>Event Sponsors</h2>
            <div class="range">
                <div class="cell-md-8 cell-lg-9">
                    <div class="sponsors">
                        <p>Our sponsors are absolutely imperative to the success of UNT Robotics. We rely on our
                            sponsors so we can continue to bring quality engineering experiences to our members and the
                            community. Every company provides us with invaluable support through both monetary and part
                            donations. Your support will be greatly appreciated and we will be flexible with connecting
                            our members with your company's message. </p>
                        <p><strong>If you would like to become a UNT Robotics sponsor, please <a href="/contact">contact
                                    us</a>!</strong></p>
                        <div class="sponsor"><img src="/images/sponsor-logos/botathon/eagles-nest.jpg"
                                                  alt="eagle's nest logo"/></div>
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
                        <p>If you have any questions at all, please get in touch with us at <a
                                    href="mailto:<?php echo EMAIL_SUPPORT; ?>"><?php echo EMAIL_SUPPORT; ?></a> or call
                            us at <a href="tel:<?php echo PHONE_NUMBER; ?>"><?php echo PHONE_NUMBER_FORMATTED; ?></a>.
                        </p>
                        <p>We're always happy to help, no matter how small or big your question is.</p>
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
	// var currentNav = "#nav-info"
	// var currentAnchor = '#info'
	var anchors = ['info','register','schedule','parts-list','teams','sponsors','contacts']
	var curIndex = 0
	var lastScrollTop = $(window).scrollTop()
	const downTolerance = 0.25
	const upTolerance = 0.5

	$(window).scroll(function(){
		windoo = $(this)
		let currentScrollTop = windoo.scrollTop()
		let windowHeight = windoo.height()
		let scrollBottom = currentScrollTop + windowHeight
		function elementInView(elem)
		{
			let elemTop = $(elem).offset().top + parseInt($(elem).css('padding-top'),10)
			var elemBottom = elemTop + $(elem).height()
			return (elemTop <= currentScrollTop && elemBottom >= currentScrollTop) || (elemTop >= currentScrollTop && elemTop <= scrollBottom) || (elemBottom >= currentScrollTop && elemBottom <= scrollBottom)
		}
		function getTop(e){return $(e).offset().top + parseInt($(e).css('padding-top'),10)}
		function getBottom(e) {return getTop(e)+ $(e).height()}
		function switchActive(e,i){
			$('#nav-'.concat(anchors[curIndex])).removeClass('active')
			$('#nav-'.concat(e)).addClass('active')
			curIndex = i
		}

		if(currentScrollTop > lastScrollTop){ // scrolled down
			if(curIndex!==6 && Math.floor(getBottom('footer'))<= scrollBottom)
			{
				switchActive('contacts',6)
				lastScrollTop = currentScrollTop
				return
			}
			if(getBottom('#'.concat(anchors[curIndex]))<= currentScrollTop +windowHeight*downTolerance){
				if(curIndex===6) {
					lastScrollTop = currentScrollTop
					return
				}
				for(let i = curIndex+1;i<7;i++){
					if(elementInView('#'.concat(anchors[i]))){
						switchActive(anchors[i],i)
						break
					}
				}
			}
		} else{ // scrolled up
			if(getTop('#'.concat(anchors[curIndex]))>=currentScrollTop+windowHeight*(1-upTolerance)){
				if(curIndex===0) {
					lastScrollTop = currentScrollTop
					return
				}
				for(let i = curIndex-1;i>=0;i--){
						if (elementInView('#'.concat(anchors[i]))) {
							switchActive(anchors[i], i)
							break
						}
				}
			}
		}
		lastScrollTop = currentScrollTop
	});

    $(document).ready(function () {
        // Add smooth scrolling to all links
        $("#botathon-navigation ul li a").on('click', function (event) {

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
                }, 800, function () {

                    // Add hash (#) to URL when done scrolling (default click behavior)
                    window.location.hash = hash;
                });
            } // End if
        });
    });
</script>
