<?php
require('../template/top.php');
require_once(BASE . '/template/functions/payment_button.php');
head('Sponsorships', true);

$payment_button = new PaymentButton(
    'UNT Robotics General Sponsorship or Donation',
    null,
    'Donate Now'
);
$payment_button->set_complete_return_uri('/sponsorships/donate/thank-you');
?>
<section class="section-50 section-md-75 section-lg-100">
    <div class="shell range-offset-1">
        <div class="range">
            <div class="cell-lg-6">
                <h1>Sponsorships</h1>
					<h3>Who Are We</h3>
							<p>UNT Robotics is made up of students who are driven by a passion for engineering. We put together our efforts to introduce students to engineering integrated with computer science and to promote a fun engineering learning environment at UNT. Our goal is to grow UNT students’ interests in engineering and provide them with opportunities to apply what they learn in class through competitions that offer hands-on experiences. To achieve this, we participate in events and projects such as the NASA JPL Rover and the NASA Student Launch Initiative competition. Not only that, we also host weekly workshops, and our annual Botathon competition.</p>
					<h3>Our Activities</h3>
							<p>The NASA Student Launch Initiative at UNT Robotics is under the supervision of our Aerospace division, which produces innovative rockets, rocket propulsion systems, payload delivery systems, and more to complete challenging tasks set by NASA each year. The UNT NASA SLI team aims to not only become a highly competitive team but to become a nationally recognized team to create a legacy to carry on for future students.</p>
							<p>During our weekly workshops, selected members of the organization share their knowledge and passion in specific fields such as embedded systems, AI and machine learning, and model rocketry. We provide students with a chance to have hands-on experience and practice with a variety of STEM disciplines.</p>
							<p>We also host our annual Botathon competition where all UNT students regardless of major or experience are invited to compete in a one-day design, test, build, and compete robotics marathon, to help them grow passion for engineering and learn new problem-solving skills. To truly enrich the experience, we provide every participant of the competition with everything they will need for the day, including parts, kits, tools, guides, mentorship, and food.</p>
					<h3>Why Should You Sponsor Us?</h3>
						<p>Because of our non-commercial business model, we rely entirely on sponsorships and donations for funding our events and sponsors are absolutely crucial to the success of UNT Robotics. Your support will be greatly appreciated, and we will be flexible with connecting our members with your company’s message.</p>
            </div>
			<div class="cell-lg-12 offset-lg-top-50">
				<?php echo $payment_button->get_button()->button; ?> <!--La Paypal button-->
			</div>
        </div>
    </div>
</section>

<?php
footer();
?>
