<?php
require('../template/top.php');
require_once('card.php');
head('Our Alumni', true);
$members = array();
$members[] = [
    'name'=>'Nick Tindle',
    'title'=>'1st President',
    'description'=>"I am a Computer Engineering Student, The first president, and still an active member of the group",
    'picture_uri'=>'/images/bio-pics/nick-tindle.jpg',
];
$members[] = [
    'name'=>'Juan Ruiz',
    'title'=>'2nd President',
    'description'=>"I am a Computer Engineering Student, I.T. Support Technician, and Undergrad Research Assistant at The University of North Texas. I am an active member of The Alpha Tau Omega Fraternity, Society of Hispanic Professional Engineers, Engineers without Borders, IEEE, and National Society of Professional Engineers. I have also interned at NASA, U.S. Department of Energy, and iOLAP. My focus is Embedded Systems but I have a strong passion for robotics, automation, machine learning, and artificial intelligence.",
    'picture_uri'=>'/images/bio-pics/juan-ruiz.jpg',
];
$members[] = [
    'name'=>'Katie Lee',
    'title'=>'Former Secretary',
    'description'=>"I’m a transfer student from UH. I’m studying Computer Engineering because I want to be on the front of the newest technology development, and I’m the secretary for UNT Robotics.",
    'picture_uri'=>'/images/bio-pics/katie-lee.jpg',
];
$members[] = [
    'name'=>'Michelle Rosal Vargas',
    'title'=>'Former Event Coordinator',
    'description'=>"Mechanical Engineer | Event Coordinator at UNT Robotics",
    'picture_uri'=>'/images/bio-pics/michelle-vargas.jpg',
];
$members[] = [
    'name'=>'Andrew Jarrett',
    'title'=>'Former Public Relations',
    'description'=>"Mechanical Engineer | Public Relations at UNT Robotics",
    'picture_uri'=>'/images/bio-pics/andrew-jarrett.jpg',
];
$members[] = [
    'name'=>'Nicole Kohm',
    'title'=>'Former Project Manager',
    'description'=>"I am an adaptive problem-solver with a lifetime of scientific passion. I enjoy applying what I learn to make new ideas and possibilities come to fruition. I have a strong track record of careful attention to detail and thinking outside the box. I frequently invent new technologies and methods to quickly solve multidisciplinary problems.",
    'picture_uri'=>'/images/bio-pics/nicole-kohm.jpg',
];
$members[] = [
    'name'=>'Jesse Sullivan',
    'title'=>'Former Aerospace Division Lead',
    'description'=>"Jesse is a senior mechanical engineering student interested in all things that fly and go fast. He has 4 years of experience in amateur high-power rocketry and currently holds an L1 certification with the National Association of Rocketry. He founded the Aerospace Division with UNT Robotics in order to foster interest in the hobby and provide a learning experience for anyone to become a part of.",
    'picture_uri'=>'/images/bio-pics/jesse-sullivan.jpg',
];
?>
    <main class="page-content">
        <!-- Classic Breadcrumbs-->
        <section class="breadcrumb-classic">
            <div class="rd-parallax">
                <div data-speed="0.25" data-type="media" data-url="/images/breadcrumbs-parallax.jpg" class="rd-parallax-layer"></div>
                <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
                    <div class="shell">
                        <ul class="list-breadcrumb">
                            <li><a href="/">Home</a></li>
                            <li><a href="/about">About Us</a></li>
                            <li>Our Alumni</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section class="section-75 section-md-100 section-lg-150">
            <div class="shell range-offset-1">
                <div class="range">
                    <div class="cell-lg-6">
                        <h1>Our Alumni</h1>
                        <h6>These people are the ones responsible for building our organisation and getting us to where we are today.</h6>
                    </div>
                </div>
                <?php
                foreach ($members as $member) {
                    get_member_card($member['name'], $member['title'], $member['description'], $member['picture_uri'], $member['email']??null, $member['linkedin_url'] ?? null, $member['github_url'] ?? null, $member['twitter_url'] ?? null);
                }
                ?>
            </div>
        </section>
    </main>
    <?php
footer();
?>
