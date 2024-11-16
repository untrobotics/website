<?php
require('../template/top.php');
require('card.php');
head('Our Team', true);
$members = array();

$members[] = [
    'name'=>'Sebastian King',
    'title'=>'Alumni | Team Member',
    'description'=>"Sebastian is a post-baccalaureate world languages student, with a degree in Computer Science. His role is to oversee the day-to-day running of the organisation and help ensure the organisation best serves the students at UNT. His expertise are programming and electrical engineering and he specialises in networking and remote control systems. He is also responsible for a lot of the more ambitious projects around campus, including the famous Sofabot and our re-usable weather balloon project.",
    'picture_uri'=>'/images/web-team-pics/sebastian-king.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/sebastian-king',
    'github_url'=>'https://www.github.com/sebastian-king',
];
$members[] = [
    'name'=>'Kenneth Chen',
    'title'=>'Team Member',
    'description'=>"Kenneth Chen is a Computer Science and Accounting student at UNT. As part of UNT Robotics, he's programmed helpful things, such as the controller connections for Botathon Season 3. He is also the financial director of UNT Robotics. He enjoys learnings and helping others and hopes to make the website more accessible.",
    'picture_uri'=>'/images/bio-pics/kenneth-chen.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/kenneth-w-chen',
    'github_url'=>'https://www.github.com/kenneth-w-chen',
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
$members[] = [
    'name'=>'Logan Brewer',
    'title'=>'Team Member',
    'description'=>"Logan Brewer is a Computer Science student at UNT. He is an aspiring software developer with a passion for building things. He enjoys working on electronics projects and tinkering and always approaches problems with eagerness to learn.",
    'picture_uri'=>'/images/bio-pics/logan-brewer.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/logan-brewer-26a872256/',
    'github_url'=>'https://github.com/loganthebrewer',
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
            <div class="shell range-offset-1 justify-center">
                <div class="range">
                    <div class="cell-lg-6">
                        <h1>Our Web Dev Team</h1>
                        <h6>These are the people who keep this site running. Meet our former web devs <a href="our-web-team-alumni.php">here</a>.
						</h6>
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
