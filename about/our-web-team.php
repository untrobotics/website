<?php
require('../template/top.php');
require('card.php');
head('Our Team', true);
$members = array();
$members[] = [
    'name'=>'Peyton Thibodeaux',
    'title'=>'Webmaster | Team Member',
    'description'=>"Peyton is a junior, studying computer science with a minor in mathematics. He's the webmaster for UNT Robotics and in charge of the website that you see in front of you. He enjoys learning and using new technologies and have a passion for creating things.",
    'picture_uri'=>'/images/web-team-pics/peyton-thibodeaux.jpg',
    'email'=>'webmaster@untrobotics.com',
    'linkedin_url'=>'https://www.linkedin.com/in/peyton-thibodeaux',
    'github_url'=>'https://www.github.com/peyton232',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'Sebastian King',
    'title'=>'Alumni | Team Member',
    'description'=>"Sebastian is a post-baccalaureate world languages student, with a degree in Computer Science. His role is to oversee the day-to-day running of the organisation and help ensure the organisation best serves the students at UNT. His expertise are programming and electrical engineering and he specialises in networking and remote control systems. He is also responsible for a lot of the more ambitious projects around campus, including the famous Sofabot and our re-usable weather balloon project.",
    'picture_uri'=>'/images/web-team-pics/sebastian-king.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/sebastian-king',
    'github_url'=>'https://www.github.com/sebastian-king',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'Nicholas Tindle',
    'title'=>'President | Team Member',
    'description'=>"Nicholas Tindle is a Computer Engineering student at UNT. He works in software engineering and loves hackathons. You can generally find him wearing a hat and probably a sweatshirt. He has a long history of collaboration with UNT Robotics as the first president, a loyal advisor, and now Project Manager. He has also served as an advisor to the Dean and is currently an officer of Engineering United. Nick has helped host numerous events at the university over the years. In his professional life, he works in data analysis, web development, and python scripting.",
    'picture_uri'=>'/images/web-team-pics/nick-tindle.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/ntindle',
    'github_url'=>'https://www.github.com/ntindle',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'Henry Legay',
    'title'=>'Team Member',
    'description'=>"Henry Legay is a Computer Science Student at UNT focused on web development. He is a web developer in Robotics with ready applicable experience and a willingness to learn.",
    'picture_uri'=>'/images/web-team-pics/henry-legay.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/henrylegay',
    'github_url'=>'https://www.github.com/henlegay',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'Mason Besmer',
    'title'=>'Team Member',
    'description'=>"Mason Besmer is a Computer Science student at UNT. He is a webmaster for UNT Robotics and participates in many student orgs. He creates many things and comes up with ideas for even more. In his free time, he likes to create environments for games and program the website in front of you. He likes organization and loves to tinker with things. Creator of his own magic mirror, Mason is a advocate for building his own electronics. Currently, he is working on a software solution for his Starcube. You can find out more about it on his LinkedIn.",
    'picture_uri'=>'/images/web-team-pics/mason-besmer.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/masonbesmer',
    'github_url'=>'https://www.github.com/shotbyapony',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'Aryan Damle',
    'title'=>'Team Member',
    'description'=>"Aryan Damle is a Computer Science student at UNT. He is an aspiring full stack web developer and an avid Home Assistant enthusiast. He mentors a high school robotics team and loves to work on robots in his free time. You can find him at your local car meet on weekends if he isn't busy working on a robot or fixing something in his garage.",
    'picture_uri'=>'/images/web-team-pics/aryan-damle.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/aryan-damle-8691b11bb',
    'github_url'=>'https://www.github.com/aryan-damle',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'Mary Plana',
    'title'=>'Team Member',
    'description'=>"Mary Plana is a Computer Science Student at UNT with studies focused on Front End Development. She loves designing and implementing the user interface of a project. She has a natural curiosity about the world and loves to learn and improve her skills. She is currently the president of Application Development Organization. She facilitates the meeting and leads student UI designers to design, implement, and improve the user interface of projects.",
    'picture_uri'=>'/images/web-team-pics/mary-plana.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/mary-plana',
    'github_url'=>'https://www.github.com/mcp31',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'David Thompson',
    'title'=>'Team Member',
    'description'=>"David Thompson is a Computer Science Student at UNT with studies focused on Full Stack Development. He loves solving problems, learning new things, and is currently working with a start up on a social media application that is currently in Apple's TestFlight.",
    'picture_uri'=>'/images/web-team-pics/david-thompson.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/david-thompson-000',
    'github_url'=>'https://www.github.com/davidkt99',
//    'twitter_url'=>''
];

$members[] = [
    'name'=>'Samin Yasar',
    'title'=>'Team Member',
    'description'=>"Samin Yasar is a senior Computer Science student at UNT. He is a team member of UNT Robotics webmaster helping maintain UNT Robotics website. He is also a part of the Application Development Organization as a team member. He likes to learn new things and solve complex problems.",
    'picture_uri'=>'/images/web-team-pics/samin-yasar.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/samin2668',
    'github_url'=>'https://www.github.com/samin2668',
//    'twitter_url'=>''
];

$members[] = [
    'name'=>'Kenneth Chen',
    'title'=>'Team Member',
    'description'=>"Kenneth Chen is a Computer Science and Accounting student at UNT. As part of UNT Robotics, he's programmed helpful things, such as the controller connections for Botathon Season 3. He is also the financial director of UNT Robotics. He enjoys learnings and helping others and hopes to make the website more accessible.",
    'picture_uri'=>'/images/bio-pics/kenneth-chen.jpg',
//    'email'=>'',
    'linkedin_url'=>'https://www.linkedin.com/in/kenneth-w-chen',
    'github_url'=>'https://www.github.com/kenneth-w-chen',
//    'twitter_url'=>''
];
$members[] = [
    'name'=>'Truitt Crozier',
    'title'=>'Team Member',
    'description'=>"Truitt Crozier is a sophomore Computer Science student at UNT. His passions are learning about video game console architecture, collecting music, and playing Tetris. As a part of UNT Robotics, he is involved with programming and electronics.",
    'picture_uri'=>'/images/bio-pics/truitt-crozier.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/truitt-crozier-719355293',
    'github_url'=>'https://github.com/tjcrozier',
];

$members[] = [
    'name'=>'Willow Houchin',
    'title'=>'Team Member',
    'description'=>"Willow Houchin is a Computer Science student at UNT. As a member of UNT Robotics, she has worked on the rover, creating test scripts and debugging motor connections. She enjoys all kinds of both computer science and engineering and loves learning new things",
    'picture_uri'=>'/images/bio-pics/willow-houchin.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/willow-houchin-127147252',
    'github_url'=>'https://www.github.com/WillowHouchin',
];
?>
    <main class="page-content">
        <!-- Classic Breadcrumbs-->
        <section class="breadcrumb-classic">
            <div class="rd-parallax">
                <div data-speed="0.25" data-type="media" data-url="/images/headers/web-team-page-header.jpg" class="rd-parallax-layer"></div>
                <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
                    <div class="shell">
                        <ul class="list-breadcrumb">
                            <li><a href="/">Home</a></li>
                            <li><a href="/about">About Us</a></li>
                            <li>Our Web Team</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section class="section-75 section-md-100 section-lg-150">
            <div class="shell range-offset-1">
                <div class="range">
                    <div class="cell-lg-6">
                        <h1>Our Web Dev Team</h1>
                        <h6>These are the people who keep this site running.</h6>
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
