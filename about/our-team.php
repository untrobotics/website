<?php
require('../template/top.php');
head('Our Team', true);

/*
 * Current officers. Source: the club's "Officer Contact Information" roster
 * (2026 Spring). Names + roles only — no personal contact details on the public
 * site. `null` = a currently-vacant position. Update this array each term.
 */
$roster = [
    'Executives' => [
        ['President', 'Kaitlynn Garrigus'],
        ['Vice President', 'Truitt Crozier'],
        ['Financial Director', 'Kenneth Chen'],
        ['Deputy Financial Director', null],
        ['Director of Operations', 'Logan Brewer'],
    ],
    'Team Leads' => [
        ['Mechanical Lead — SCRAPP-E', 'Preston Fager'],
        ['Mechanical Lead — Vending Machine', 'Carter Moore'],
        ['Mechanical Lead — Claw Machine', 'Jacob Ralph'],
        ['Coding Lead — SCRAPP-E', 'Ashley Tackett'],
        ['Coding Lead — Vending Machine', null],
        ['Coding Lead — Claw Machine', 'Tito Sumbo'],
        ['Botathon Lead — Mechanical', null],
        ['Botathon Lead — Coding', null],
        ['Competition Lead', 'Anirudh Varre'],
    ],
    'Managers' => [
        ['Mechanical Managers', 'Miranda (Ryan) Nystrom, Matthew Campos, Anthony Gyles II, Jose Sanchez, Josue Vergel Carrion, Armando Madrigal, Austin Hunt, Danielle Smith, Hayden Otto, Anirudh Varre'],
        ['Coding Managers', 'Anthony Gyles II, Danielle Smith'],
        ['Botathon Managers', 'Matthew Campos, Carter Moore, Danielle Smith'],
        ['Competition Manager', 'Josue Vergel Carrion'],
        ['Social Media Managers', 'Anthony Gyles II, Danielle Smith'],
        ['Multimedia Manager', 'Armando Madrigal'],
        ['Webmaster Managers', 'Anthony Gyles II, Truitt Crozier'],
        ['Inventory Managers', 'Carter Moore, Ray Garza'],
    ],
    'Coordinators &amp; Support' => [
        ['Secretary', 'Anthony Gyles II'],
        ['Workshop Coordinators', 'Anirudh Varre, Carter Moore'],
        ['Food Coordinator', 'Anthony Gyles II'],
        ['Fundraising Chair', 'Anthony Gyles II'],
        ['Sponsorship Coordinators', 'Anthony Gyles II, Armando Madrigal'],
        ['Public Relations', 'Danielle Smith, Armando Madrigal, Oren Tucker'],
        ['Recruitment Chair', 'Oren Tucker'],
        ['Event Coordinator', null],
    ],
    'Mentors' => [
        ['Mentors', 'Sebastian King, Nick Tindle, Alex Sekung'],
    ],
    'Supporting Officers (partner organizations)' => [
        ['3D Printing Club', 'Elliott Bradley'],
        ['Mean Green Rocketry', 'Austin Miller, Cameron James'],
    ],
];
?>
    <main id="main-content" class="page-content">
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
        <style>
            .roster-group { margin-top: 70px; }
            .roster-group:first-of-type { margin-top: 40px; }
            .roster-group > h3 { margin: 0 0 4px; }        /* heading hugs its own rule + entries below */
            .roster-group > hr { margin: 0 0 6px; border-top-width: 2px; }
            .roster-group > .range { margin-top: 0; }   /* theme puts 50px on .range — too much under the rule */
            .roster-entry { padding: 14px 0 12px; border-bottom: 1px solid rgba(0,0,0,.09); }
            .roster-entry .roster-role { display: block; font-weight: 700; font-size: 13px; text-transform: uppercase; letter-spacing: .04em; line-height: 1.3; }
            .roster-entry .roster-name { display: block; font-size: 16px; line-height: 1.4; margin-top: 3px; }
        </style>
        <section class="section-75 section-md-100 section-lg-150">
            <div class="shell">
                <div class="range justify-center">
                    <div class="cell-lg-8 text-center">
                        <h1>Our Team</h1>
                        <small><em>Last updated: <?php echo date("F d Y", filemtime(__FILE__)); ?></em></small>
                        <h6 role="presentation">These people are the reason for our success and expertise.</h6>
                        <p>Interested in a vacant role? <a href="/join">Join us</a> and get involved.</p>
                    </div>
                </div>
                <?php foreach ($roster as $group => $rows): ?>
                    <div class="roster-group">
                        <h3 aria-level="2"><?php echo $group; ?></h3>
                        <hr class="divider-color-2">
                        <div class="range text-left">
                            <?php foreach ($rows as [$role, $people]): ?>
                                <div class="cell-md-6 roster-entry">
                                    <span class="roster-role text-primary"><?php echo $role; ?></span>
                                    <span class="roster-name"><?php
                                        echo $people !== null
                                            ? htmlspecialchars($people, ENT_QUOTES)
                                            : '<em class="text-silver-chalice">Vacant</em>';
                                    ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <?php
footer();
?>
