<?php
require('../template/top.php');
require_once('card.php');
head('Our Team', true, false, false, "Meet the officers of UNT Robotics. These are the people driving the organization forward into the future!");
$members = array();

//co-president
$members[] = [
        'name'=>'Nicholas Tindle',
        'title'=>'President',
        'description'=>"Nicholas Tindle is a Computer Engineering student at UNT. He works in software engineering and loves hackathons. You can generally find him wearing a hat and probably a sweatshirt. He has a long history of collaboration with UNT Robotics as the first president, a loyal advisor, and now Project Manager. He has also served as an advisor to the Dean and is currently an officer of Engineering United. Nick has helped host numerous events at the university over the years. In his professional life, he works in data analysis, web development, and python scripting.",
        'picture_uri'=>'/images/bio-pics/nick-tindle.jpg',
        'email'=>'president@untrobotics.com',
        'linkedin_url'=>'https://www.linkedin.com/in/ntindle',
        'github_url'=>'https://www.github.com/ntindle',
];
//vice president
$members[] = [
        'name'=>'Laurance (Murphy) Boyd',
        'title'=>'Vice President',
        'description'=>'Laurance Boyd is a Computer Science student at UNT. He is a former Marine with four years of engineering experience in the fleet Marine Corps. He utilized his passion for language learning and leadership to ensure the success of mission critical objectives with Japanese partner forces. He chose to serve as the vice president of UNT Robotics due to the challenging and complex nature of the projects. In his free time he enjoys studying world languages, philosophy and martial arts.',
        'picture_uri'=>'/images/bio-pics/laurance-boyd.jpg',
        'email'=>'vice-president@untrobotics.com',
];
//financial director
$members[] = [
    'name'=>'Kenneth Chen',
    'title'=>'Financial Director',
    'description'=>"Kenneth Chen is a Computer Science and Accounting student at UNT. As an officer of UNT Robotics, he's been a part of various projects, such as organizing Botathon, working on the website, and competing in NASA USLI. As the financial director, he does the bookkeeping and prepares the financial statements for the organization's year end.",
    'picture_uri'=>'/images/bio-pics/kenneth-chen.jpg',
    'email'=>'financial-director@untrobotics.com',
    'linkedin_url'=>'https://www.linkedin.com/in/kenneth-w-chen',
    'github_url'=>'https://www.github.com/kenneth-w-chen',
];
//deputy financial director
$members[] = [
    'name'=>'Mahathi Sriji',
    'title'=>'Deputy Financial Director and Inventory Manager',
    'description'=>"Mahathi Sriji is a Mechanical and Energy Engineering student at UNT. Her role is to keep track and organize inventory for the robotics organization and assist in managing finances. She is currently working on multiple engineering projects such as system controls using simulink, a new rotorcraft model, and a recreational robot. She also teaches percussion and performed in many areas.",
    'picture_uri'=>'/images/bio-pics/mahathi-sriji.jpg',
    'email'=>'deputy-financial-director@untrobotics.com',
    'linkedin_url'=>'https://www.linkedin.com/in/mahathisriji'
];
// Social Media Manager
$members[] = [
    'name'=>'Brielle Brown',
    'title'=>'Social Media Manager and Multimedia Manager',
    'description'=>"Brielle Brown is an Information Technology undergraduate student at UNT. Her role is to maintain the social media platforms for the organization, as well as creating content for said platforms. She has been in several previous officer positions relating to social media, and has extensive experience regarding graphic design, social media marketing, photography, and more. She also loves doing freelance concept art in her free time.",
    'picture_uri'=>'/images/bio-pics/brielle-brown.jpg',
    'email'=>'corp-relations@untrobotics.com',
];
// (lead) Webmaster
$members[] = [
    'name'=>'Truitt Crozier',
    'title'=>'Webmaster and Inventory Manager',
    'description'=>"Truitt Crozier is a sophomore Computer Science student at UNT. His passions are learning about video game console architecture, collecting music, and playing Tetris. As a part of UNT Robotics, he is involved with programming and electronics.",
    'picture_uri'=>'/images/bio-pics/truitt-crozier.jpg',
    'email'=>'webmaster@untrobotics.com',
    'linkedin_url'=>'https://www.linkedin.com/in/truitt-crozier-719355293',
    'github_url'=>'https://github.com/tjcrozier',
];
// Project Manager
$members[] = [
    'name'=>'Farhan Ar Rafi',
    'title'=>'Project Manager',
    'description'=>"Farhan is a Computer Science graduate student at UNT. As the project manager for UNT Robotics, he oversees the development of the NASA Rover project. He has previous experience in software engineering and is proficient in mobile and web apps, Android firmware, and IoT technologies. His PhD research focuses on enhancing the quality of life for visually and physically impaired individuals. On the weekends, you might bump into him in one of the hackathons or cybersecurity meetups around DFW.",
    'picture_uri'=>'/images/bio-pics/farhan-ar-rafi.jpg',
    'email'=>'project-manager@untrobotics.com',
    'linkedin_url'=>'https://www.linkedin.com/in/farhanarrafi',
    'github_url'=>'https://www.github.com/farhanarrafi',
];
// Corporate Relations
$members[] = [
    'name'=>'Sophia Casas',
    'title'=>'Corporate Relations and Sponsorship Coordinator',
    'description'=>"Sophia Casas is an undergraduate Pre-Med Biology Major. She currently serves as the sponsorship coordinator and as a mechanical team lead. She has previously collaborated and worked on several nationally award winning robots. She gained leadership experience by working with a career and technical education organization and served as the president of the organization for the state of Texas. Her main focus is sponsorship and outreach, having previously assisted in many technical education bills, such as Perkin V, being passed by U.S. Congress, totalling over 1.4 billion dollars annually. She is a proud dog mom and you can find her most days exploring Denton with her pup Loki.",
    'picture_uri'=>'/images/bio-pics/sophia-casas.jpg',
    'email'=>'corp-relations@untrobotics.com',
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
                            <li>Our Team</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section class="section-75 section-md-100 section-lg-150">
            <div class="shell range-offset-1 justify-center">
                <div class="range">
                    <div class="cell-lg-6">
                        <h1>Our Team</h1>
                        <small><em>Last updated: <?php echo date ("F d Y", filemtime(__FILE__)); ?></em></small>
                        <h6>These people are the reason for our success and expertise.</h6>
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
