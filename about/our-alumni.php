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
$members[] = [
    'name'=>'Sebastian King',
    'title'=>'Former Co-President',
    'description'=>"Sebastian is a post-baccalaureate world languages student, with a degree in computer science. His role as co-president was to oversee the day-to-day running of the organisation and help ensure the organization best serves the students at UNT. His expertise are programming and electrical engineering and he specializes in networking and remote control systems. He was, and still is, responsible for a lot of the more ambitious projects around campus, including the famous Sofabot and our re-usable weather balloon project.",
    'picture_uri'=>'/images/bio-pics/sebastian-king.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/sebastian-king',
    'github_url'=>'https://www.github.com/sebastian-king',
    'twitter_url'=>'https://www.twitter.com/@thekingseb'
];
$members[] = [
    'name'=>'Lauren Caves',
    'title'=>'Former Co-President',
    'description'=> "She’s a woman of action, eternal optimist, and a passionate speaker. As a mechanical engineer, her enthusiasm for the art of engineering is vast and the hunger for discovery drives her motivation which led to many successes during her undergraduate years, although not easily. She has gone through many failures and complicated situations which shaped a new passion to inspire the youth and other students to overcome diversity and really fight for their dreams. She believes everyone is destined for greatness, and that it's important to not let the minor roadblocks we stumble upon crush the stars we wish upon.",
    'picture_uri'=>'/images/bio-pics/lauren-caves.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/lauren-caves',
];
$members[] = [
    'name'=>'Tyler Adam Martinez',
    'title'=>'Former Vice President and Former Financial Director',
    'description'=>'Tyler is a software consultant with an electrical engineering degree from UNT and a minor in biomedical engineering. At UNT Robotics, he focused on robotics and automation, and loved designing cool circuits and building electronics.',
    'picture_uri'=>'/images/bio-pics/tyler-adam-martinez.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/tyleradammartinez',
    'github_url'=>'https://www.github.com/TylerAdamMartinez',
];
$members[] = [
    'name'=>'Andrew Paul',
    'title'=>'Former Financial Director',
    'description'=>'The Eagle Scout, Black Belt and Rescue scuba diver brought his vast experience keeping the funds up and in check for the the future development of UNT Robotics. Andrew Paul holds a mechanical engineering degree from UNT and loves innovation.',
    'picture_uri'=>'/images/bio-pics/andrew-paul.jpg',
];
$members[] = [
    'name'=>'Jacob Gomez',
    'title'=>'Former Public Relations',
    'description'=>'Jacob is a current Computer Science student at UNT. Although he balances school and work he finds time for extracurriculars. He has always enjoyed being apart of different organizations and getting to know new people. He loves using all the different software to model, create and design things for the organization or for his own personal projects.',
    'picture_uri'=>'/images/bio-pics/jacob-gomez.jpg',
];
$members[] = [
    'name'=>'Abdus Samee',
    'title'=>'Former Event Coordinator',
    'description'=>"With an ECE master's, Abdus Samee's love for engineering must be seen to be believed. Being a former Toastmaster, he has a flair for speaking which convinces the listener to accept the facts that he puts forward. His never-ending hunger for knowledge has enabled him to achieve a lot in a short time. Education is an ornament in prosperity and a refuge in adversity is what he says. Progress never comes without struggles, but his persistence helped him reach his dream destination. He was also the Vice Chair for UNT IEEE Robotics and Automation Society, and a IEEE Eta Kappa Nu Honor Student. The words of Ford inspired him to keep going. 'When everything seems to be going against you, remember that the airplane takes off against the wind and not with it.",
    'picture_uri'=>'/images/bio-pics/abdus-samee.jpg',
];
$members[] = [
    'name'=>'Ally Flores',
    'title'=>'Former Social Media Manager',
    'description'=>"Ally was a freshman majoring in mechanical engineering technology. She’s been in robotics since high school and was excited to continue it through college. She loves trying new hobbies and making new friends!",
    'picture_uri'=>'/images/bio-pics/ally-flores.jpg',
];
$members[] = [
    'name'=>'Peyton Thibodeaux',
    'title'=>'Former Webmaster',
    'description'=>"Peyton holds a degree in computer science from UNT with a minor in mathematics. He's a former webmaster for UNT Robotics and was in charge of the website that you see in front of you. He enjoys learning and using new technologies and have a passion for creating things.",
    'picture_uri'=>'/images/bio-pics/peyton-thibodeaux.jpg',
    'linkedin_url'=>'https://www.linkedin.com/in/peyton-thibodeaux',
];
$members[] = [
    'name'=>'Ashank Annam',
    'title'=>'Former Corporate Relations',
    'description'=>"Ashank was a freshman studying Business Computer Information Systems. He has been interested in robotics since high school. He loves to meet new people and learn new technologies.",
    'picture_uri'=>'/images/bio-pics/ashank-annam.jpg',
];
$members[] = [
    'name'=>'Joseph moore',
    'title'=>'Former Aerospace Division Lead',
    'description'=>"Joseph holds a mechanical engineering bachelor's. Formerly a US Marine Master Explosive Ordnance Disposal Technician (EOD), he has always tried to tinker with things and figure out how they work and is passionate about establishing and growing our High-Power Rocketry team into a nationally competitive force. Both through STEM outreach efforts and internal events, he hopes to generate interest and encourage everyone to get into hobby rocketry, and the sport of high power rocketry.",
    'picture_uri'=>'/images/bio-pics/joe-moore.jpg',
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
            <div class="shell range-offset-1 justify-center">
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
