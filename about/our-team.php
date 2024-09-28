<?php
require('../template/top.php');
require('../template/functions/card.php');
head('Our Team', true);
$members = array();

//co-president
$members[] = [
        'name'=>'Sebastian King',
        'title'=>'Co-President',
        'description'=>"Sebastian is a post-baccalaureate world languages student, with a degree in Computer Science. His role is to oversee the day-to-day running of the organisation and help ensure the organisation best serves the students at UNT. His expertise are programming and electrical engineering and he specialises in networking and remote control systems. He is also responsible for a lot of the more ambitious projects around campus, including the famous Sofabot and our re-usable weather balloon project.",
        'picture_uri'=>'/images/bio-pics/sebastian-king.jpg',
        'email'=>'president@untrobotics.com',
        'linkedin_url'=>'https://www.linkedin.com/in/sebastian-king',
        'github_url'=>'https://www.github.com/sebastian-king',
        'twitter_url'=>'https://www.twitter.com/@thekingseb'
];
//co-president2
$members[] = [
        'name'=>'Lauren Caves',
        'title'=>'Co-President',
        'description'=> "She’s a woman of action, eternal optimist, and a passionate speaker. As an undergraduate mechanical engineering student, her enthusiasm for the art of engineering is vast and the hunger for discovery drives her motivation which has led to many successes during her undergraduate years. Although not easily. She has gone through many failures and complicated situations which shaped a new passion to inspire the youth and other students to overcome diversity and really fight for their dreams. She believes everyone is destined for greatness, and that it's important to not let the minor roadblocks we stumble upon crush the stars we wish upon.",
        'picture_uri'=>'/images/bio-pics/lauren-caves.jpg',
        'email'=>'president@untrobotics.com',
        'linkedin_url'=>'https://www.linkedin.com/in/lauren-caves',
//    'github_url'=>'',
//    'twitter_url'=>''
];
//vice president
$members[] = [
        'name'=>'Tyler Adam Martinez',
        'title'=>'Vice President and Financial Director',
        'description'=>'Tyler is a junior currently studying electrical engineering with a minor in biomedical engineering. He focuses on robotics and automation, and loves designing cool circuits and building electronics.',
        'picture_uri'=>'/images/bio-pics/tyler-adam-martinez.jpg',
        'email'=>'vice-president@untrobotics.com',
        'linkedin_url'=>'https://www.linkedin.com/in/tyleradammartinez',
        'github_url'=>'https://www.github.com/TylerAdamMartinez',
    //    'twitter_url'=>''
];
// financial director
//$officers[] = [
//        'name'=>'Tyler Adam Martinez',
//        'title'=>'Vice President and Financial Director',
//        'description'=>'Tyler is a junior currently studying electrical engineering with a minor in biomedical engineering. He focuses on robotics and automation, and loves designing cool circuits and building electronics.',
//        'picture_uri'=>'/images/bio-pics/tyler-adam-martinez.jpg',
//        'email'=>'treasury@untrobotics.com',
//        'linkedin_url'=>'https://www.linkedin.com/in/tyleradammartinez',
//        'github_url'=>'https://www.github.com/TylerAdamMartinez',
//    //    'twitter_url'=>''
//];

// deputy financial director
$members[] = [
    'name'=>'Andrew Paul',
    'title'=>'Deputy Financial Director',
    'description'=>'The Eagle Scout, Black Belt and Rescue scuba diver brings his vast experience keeping the funds up, and in check for the the future development of the robotics club. Andrew Paul is a freshman mechanical engineering student and lover of innovation. New to the scene with charisma to spare and a passion for engineering. He can be found climbing at the UNT Rockwall,  or in his dorm finding out new ways to build and code his next project while assisting in club finances.',
    'picture_uri'=>'/images/bio-pics/andrew-paul.jpg',
    'email'=>'deputy-financial-director@untrobotics.com',
//    'linkedin_url'=>'',
//    'github_url'=>'',
//    'twitter_url'=>''
];
// Public Relations
$members[] = [
    'name'=>'Jacob Gomez',
    'title'=>'Public Relations',
    'description'=>'Jacob is a current Computer Science student at UNT.Although he balances school and work he finds time for extracurriculars.He has always enjoyed being apart of different organizations and getting to know new people. He loves using all the different software to model, create and design things for the club or for his own personal projects.',
    'picture_uri'=>'/images/bio-pics/jacob-gomez.jpg',
    'email'=>'public-relations@untrobotics.com',
//    'linkedin_url'=>'',
//    'github_url'=>'',
//    'twitter_url'=>''
];

// Event Coordinator
$members[] = [
    'name'=>'Abdus Samee',
    'title'=>'Event Coordinator',
    'description'=>"Abdus Samee is an ECE graduate whose love for engineering must be seen to be believed. Being a former Toastmaster, he has a flair for speaking which convinces the listener to accept the facts that he puts forward. His never ending hunger for knowledge has enabled him to achieve a lot in a short time. Education is an ornament in prosperity and a refuge in adversity is what he says. Progress never comes without struggles, but his persistence helped him reach his dream destination. He is also the Vice Chair for UNT IEEE Robotics and Automation Society, and a IEEE Eta Kappa Nu Honor Student. The words of Ford inspired him to keep going. 'When everything seems to be going against you, remember that the airplane takes off against the wind and not with it.",
    'picture_uri'=>'/images/bio-pics/abdus-samee.jpg',
    'email'=>'event-coordinator@untrobotics.com',
//    'linkedin_url'=>'',
//    'github_url'=>'',
//    'twitter_url'=>''
];

// Social Media Manager
$members[] = [
    'name'=>'Ally Flores',
    'title'=>'Social Media Manager',
    'description'=>"Ally is a freshman majoring in mechanical engineering technology. She’s been in robotics since high school and is excited to continue it through college. She loves trying new hobbies and making new friends!",
    'picture_uri'=>'/images/bio-pics/ally-flores.jpg',
    'email'=>'corp-relations@untrobotics.com',
//    'linkedin_url'=>'',
//    'github_url'=>'',
//    'twitter_url'=>''
];

// (lead) Webmaster
$members[] = [
    'name'=>'Peyton Thibodeaux',
    'title'=>'Webmaster',
    'description'=>"Peyton is a junior, studying computer science with a minor in mathematics. He's the webmaster for UNT Robotics and in charge of the website that you see in front of you. He enjoys learning and using new technologies and have a passion for creating things.",
    'picture_uri'=>'/images/bio-pics/peyton-thibodeaux.jpg',
    'email'=>'webmaster@untrobotics.com',
    'linkedin_url'=>'https://www.linkedin.com/in/peyton-thibodeaux',
//    'github_url'=>'',
//    'twitter_url'=>''
];

// Project Manager
$members[] = [
    'name'=>'Nicholas Tindle',
    'title'=>'Project Manager',
    'description'=>"Nicholas Tindle is a Computer Engineering student at UNT. He works in software engineering and loves hackathons. You can generally find him wearing a hat and probably a sweatshirt. He has a long history of collaboration with UNT Robotics as the first president, a loyal advisor, and now Project Manager. He has also served as an advisor to the Dean and is currently an officer of Engineering United. Nick has helped host numerous events at the university over the years. In his professional life, he works in data analysis, web development, and python scripting.",
    'picture_uri'=>'/images/bio-pics/nick-tindle.jpg',
    'email'=>'project-manager@untrobotics.com',
//    'linkedin_url'=>'',
//    'github_url'=>'',
//    'twitter_url'=>''
];

// Corporate Relations
$members[] = [
    'name'=>'Ashank Annam',
    'title'=>'Corporate Relations',
    'description'=>"Ashank is a freshman studying Business Computer Information Systems. He has been interested in robotics since high school. He loves to meet new people and learn new technologies.",
    'picture_uri'=>'/images/bio-pics/ashank-annam.jpg',
    'email'=>'corp-relations@untrobotics.com',
//    'linkedin_url'=>'',
//    'github_url'=>'',
//    'twitter_url'=>''
];

// Joe
$members[] = [
    'name'=>'Joseph moore',
    'title'=>'Aerospace Division Lead',
    'description'=>"Joseph is a Junior Mechanical Engineering student. Formerly a US Marine Master Explosive Ordnance Disposal Technician (EOD), he has always tried to tinker with things and figure out how they work and is passionate about establishing and growing our High-Power Rocketry team into a nationally competitive force. Both through STEM outreach efforts and internal events, he hopes to generate interest and encourage everyone to get into hobby rocketry, and the sport of high power rocketry.",
    'picture_uri'=>'/images/bio-pics/joe-moore.jpg',
    'email'=>'aerospace@untrobotics.com',
//    'linkedin_url'=>'',
//    'github_url'=>'',
//    'twitter_url'=>''
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
            <div class="shell range-offset-1">
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
