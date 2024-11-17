<?php
require('../template/top.php');
require_once('card.php');
head('Our Web Team Alumni', true);
$members = array();
$members[] = [
	'name'=>'Peyton Thibodeaux',
	'title'=>'Webmaster',
	'description'=>"Peyton is a junior, studying computer science with a minor in mathematics. He's the webmaster for UNT Robotics and in charge of the website that you see in front of you. He enjoys learning and using new technologies and have a passion for creating things.",
	'picture_uri'=>'/images/web-team-pics/peyton-thibodeaux.jpg',
	'email'=>'webmaster@untrobotics.com',
	'linkedin_url'=>'https://www.linkedin.com/in/peyton-thibodeaux',
	'github_url'=>'https://www.github.com/peyton232',
//    'twitter_url'=>''
];
$members[] = [
	'name'=>'Nicholas Tindle',
	'title'=>'Team Member',
	'description'=>"Nicholas Tindle is a Computer Engineering student at UNT. He works in software engineering and loves hackathons. You can generally find him wearing a hat and probably a sweatshirt. He has a long history of collaboration with UNT Robotics as the first president, a loyal advisor, and now Project Manager. He has also served as an advisor to the Dean and is currently an officer of Engineering United. Nick has helped host numerous events at the university over the years. In his professional life, he works in data analysis, web development, and python scripting.",
	'picture_uri'=>'/images/web-team-pics/nick-tindle.jpg',
	'linkedin_url'=>'https://www.linkedin.com/in/ntindle',
	'github_url'=>'https://www.github.com/ntindle',
];
$members[] = [
	'name'=>'Henry Legay',
	'title'=>'Team Member',
	'description'=>"Henry Legay is a Computer Science Student at UNT focused on web development. He is a web developer in Robotics with ready applicable experience and a willingness to learn.",
	'picture_uri'=>'/images/web-team-pics/henry-legay.jpg',
	'linkedin_url'=>'https://www.linkedin.com/in/henrylegay',
	'github_url'=>'https://www.github.com/henlegay',
];

$members[] = [
	'name'=>'Mason Besmer',
	'title'=>'Team Member',
	'description'=>"Mason Besmer is a Computer Science student at UNT. He is a webmaster for UNT Robotics and participates in many student orgs. He creates many things and comes up with ideas for even more. In his free time, he likes to create environments for games and program the website in front of you. He likes organization and loves to tinker with things. Creator of his own magic mirror, Mason is a advocate for building his own electronics. Currently, he is working on a software solution for his Starcube. You can find out more about it on his LinkedIn.",
	'picture_uri'=>'/images/web-team-pics/mason-besmer.jpg',
	'linkedin_url'=>'https://www.linkedin.com/in/masonbesmer',
	'github_url'=>'https://www.github.com/shotbyapony',
];
$members[] = [
	'name'=>'Aryan Damle',
	'title'=>'Team Member',
	'description'=>"Aryan Damle is a Computer Science student at UNT. He is an aspiring full stack web developer and an avid Home Assistant enthusiast. He mentors a high school robotics team and loves to work on robots in his free time. You can find him at your local car meet on weekends if he isn't busy working on a robot or fixing something in his garage.",
	'picture_uri'=>'/images/web-team-pics/aryan-damle.jpg',
	'linkedin_url'=>'https://www.linkedin.com/in/aryan-damle-8691b11bb',
	'github_url'=>'https://www.github.com/aryan-damle',
];
$members[] = [
	'name'=>'Mary Plana',
	'title'=>'Team Member',
	'description'=>"Mary Plana is a Computer Science Student at UNT with studies focused on Front End Development. She loves designing and implementing the user interface of a project. She has a natural curiosity about the world and loves to learn and improve her skills. She is currently the president of Application Development Organization. She facilitates the meeting and leads student UI designers to design, implement, and improve the user interface of projects.",
	'picture_uri'=>'/images/web-team-pics/mary-plana.jpg',
	'linkedin_url'=>'https://www.linkedin.com/in/mary-plana',
	'github_url'=>'https://www.github.com/mcp31',
];
$members[] = [
	'name'=>'David Thompson',
	'title'=>'Team Member',
	'description'=>"David Thompson is a Computer Science Student at UNT with studies focused on Full Stack Development. He loves solving problems, learning new things, and is currently working with a start up on a social media application that is currently in Apple's TestFlight.",
	'picture_uri'=>'/images/web-team-pics/david-thompson.jpg',
	'linkedin_url'=>'https://www.linkedin.com/in/david-thompson-000',
	'github_url'=>'https://www.github.com/davidkt99',
];
$members[] = [
	'name'=>'Samin Yasar',
	'title'=>'Team Member',
	'description'=>"Samin Yasar is a senior Computer Science student at UNT. He is a team member of UNT Robotics webmaster helping maintain UNT Robotics website. He is also a part of the Application Development Organization as a team member. He likes to learn new things and solve complex problems.",
	'picture_uri'=>'/images/web-team-pics/samin-yasar.jpg',
	'linkedin_url'=>'https://www.linkedin.com/in/samin2668',
	'github_url'=>'https://www.github.com/samin2668',
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
                            <li>Our Web Team Alumni</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section class="section-75 section-md-100 section-lg-150">
            <div class="shell range-offset-1">
                <div class="range">
                    <div class="cell-lg-6">
                        <h1>Our Web Team Alumni</h1>
                        <h6>Meet the talented developers who helped shape our website into what it is today.</h6>
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
