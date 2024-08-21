<?php
require('../template/top.php');
require(BASE . '/api/discord/bots/admin.php');
//require(BASE . '/api/google/count_majors.php');

head('About Us', true);
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
                  <li><a href="/about">About Us</a></li>
                  <li>What We Do
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </section>
        <section class="section-75 section-md-100 section-lg-150">
          <div class="shell">
            <div class="range range-md-justify">
              <div class="cell-md-8 cell-lg-7 cell-xl-6">
                <div class="inset-md-right-30 inset-lg-right-0">
                  <h1>What We Do</h1>
                    <small><em>Last updated: <?php echo date ("F d Y", filemtime(__FILE__)); ?></em></small>
		            <h3>Overview</h3>
                  <p>UNT Robotics is a broad engineering student organisation at the <a href="https://www.unt.edu/">University of North Texas</a>.</p>
				  <p>We focus on developing student's skills in engineering &amp; robotics, which involves a range of beginner workshops, industry talks, robotics-based hackathons, recreational projects and competitions.</p>
                  <div class="well-custom">
                    <h5><strong><?php echo AdminBot::get_member_count(); ?></strong> members from <strong>12</strong> majors.</h5>
                  </div>
                  <p>We've grown a lot since our conception in late 2018. UNT's Engineering program teaches students much of the theory of needed to flourish, however there was a gap for the invaluable hands on experience that we give our members. As a result, we've become a part of the growth and development of students here at UNT.</p>
                  <p>As a student organisation, we also develop the social skills of students. We host social and team building events and extensively network with industry partners who value the skills that we have given their recruits.</p>

                    <h3>Workshops</h3>
				        <p>We host bi-weekly workshops on topics from electrical circuits and programming microcontrollers to computer-aided design (CAD) and image processing. These workshops are tailored to teaching new skills to novices, they are perfect for new students to get a head start in their field or for students in other majors to branch our and learn about robotics.</p>
				        <p>The workshops consist of a 1-2 hour course held after our evening meetings where one can turn up with no knowledge of the topic and leave with tangible skills to take home. We provide all of the equipment needed and bring in experts to teach each workshop.</p>

                    <h3>Socials</h3>
				        <p>No student organisation is complete without the friends & family that one makes throughout their tenure. We develop our students as people, not just academically. The aspect of building relationships is just as important as any academic teaching because the relationships that students forge when taking part in our social events can improve their academic life by engaging with like-minded and driven individuals.</p>

                    <h3>Talks</h3>
				        <p>When professionals from diverse engineering fields come together to present for our members, they aren't just inspiring the next generation of engineers, but they are cultivating ideas, forming bonds, and imparting unparalleled strategies on how to navigate the job market. These invaluable opportunities provide our members with a unique competitive advantage when venturing out to apply for internships and careers that will empower them for a lifetime.</p>

                    <h3>Networking</h3>
                        <p>A big part of our development is encouraging & facilitating networking. This is mutually beneficial for the students and companies alike who love to recruit our alumni. We also have working relationships with companies who offer talks, parts and equipment and mentorship every semester.</p>

                    <h3>Botathon</h3>
				        <p>Founded in 2019, Botathon is a cross-disciplinary bracketed competition hosted by students, for students. It's themed exclusively each year to broaden the skill sets of the entrants. Our purpose is to inspire moments of creativity, enable personal and professional growth, and ignite change by sharing the power of engineering. We strive to lead each participant to become a leader in their team to nurture critical thinking and prepare them to find profound solutions to complex problems even after they graduate.</p>
                        <p>	During the course of 12 hours, teams are required to prioritize the planning and execution of the general principles adopted by that year of Botathon. All UNT students are eligible to enter for free, and materials for the robot build are provided as well as food, and prizes for the champions!</p>

                    <h3>Competitions</h3>
				        <p>Competitions create a platform for individuals to share, innovate, and build on ideas in the pursuit of discovery, development, and integrity. We aim to compel students to thrive by working together in challenging scenarios that make use of each individual's strengths while also improving shortcomings. For many, this is one of the first opportunities to work cross-disciplinary and see how other fields build upon their own, while also learning the basics of other disciplines simultaneously.</p>

                    <h3>Recreational Projects</h3>
				        <p>These fun projects are for anyone and everyone that just wants to have fun and work on a project together. These projects tend to be very long-term because we don't require any time commitment, allowing people to come and go as they wish. Anyone can propose a project to work on, and so long as they get a team together we will support them in building it.</p>

                    <h3>Equipment Checkout</h3>
				        <p>Equipment checkout is suspended due to COVID-19.</p>

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
<?php
footer();
?>
