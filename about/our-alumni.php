<?php
require('../template/top.php');
head('Our Alumni', true);

/*
 * Past officers, grouped by the academic year they joined the officer team.
 * Compiled from the club's historical officer records ("Old Officers Sheet" +
 * per-year rosters). Names + role(s) + term only — no personal details, no
 * photos. CURRENT officers live on /about/our-team and are intentionally not
 * duplicated here. Roughly chronological; older terms are approximate.
 */
$alumni_by_year = [
    '2018 – 2019' => [
        ['Juan Ruiz', 'Vice President, Co-President (2018–2020)'],
        ['Alex Ferguson', 'Vice President, Co-President (2019–2020)'],
        ['Timothy Stern', 'Financial Director'],
        ['David Woodward', 'Event Coordinator'],
        ['Kyle Derrough', 'Public Relations'],
        ['Eric King', 'Inventory Manager'],
        ['Chase Summers', 'Secretary'],
        ['Alec Slonina', 'Secretary'],
        ['Michelle Victoria', 'Public Relations'],
    ],
    '2019 – 2020' => [
        ['Tyler Adam Martinez', 'Deputy Financial Director, Financial Director, Vice President (2019–2023)'],
        ['Nicole Kohm', 'Project Manager'],
        ['Michelle Rosal Vargas', 'Event Coordinator'],
        ['Andrew Jarrett', 'Public Relations'],
        ['Jacob Favalora', 'Inventory Manager'],
        ['Katie Lee', 'Secretary'],
        ['Jesus Patino', 'Public Relations'],
        ['Noel Winslow', 'Corporate Relations'],
    ],
    '2020 – 2021' => [
        ['Lauren Caves', 'Secretary, Vice President, Co-President'],
        ['Andrew Paul', 'Deputy Financial Director, Financial Director (2020–2022)'],
        ['Peyton Thibodeaux', 'Webmaster, Inventory Manager (2020–2022)'],
        ['Javier Solis', 'Financial Director'],
        ['Jesse Sullivan', 'Aerospace Division Lead'],
        ['Abdus Samee', 'Event Coordinator'],
        ['Ashank Annam', 'Corporate Relations'],
        ['Allegra “Ally” Flores', 'Social Media Manager'],
        ['Jacob Gomez', 'Public Relations (2021–2022)'],
        ['Christopher Gonzales', 'Multimedia Manager'],
        ['Megan McAdams', 'Secretary'],
    ],
    '2021 – 2022' => [
        ['Andy Hooker', 'Co-President'],
        ['Ibi Eni', 'Event Coordinator, Vice President, Co-President (2021–2023)'],
        ['Benjamin Bailey', 'Aerospace Division Deputy (2021–2023)'],
        ['Abdullah “Wanhack” AlAbdullRazzaq', 'Cyber Security Division Lead'],
        ['Kalyan Adhikari', 'Workshop Coordinator'],
        ['Ali Hammoud', 'Event Coordinator'],
        ['Brooke Qiao', 'Social Media Manager'],
        ['Cameron Smyrl', 'Secretary'],
    ],
    '2022 – 2023' => [
        ['Johnathan Stewart', 'Vice President'],
        ['Ross Pulliam', 'Project Manager (2022–2024)'],
        ['Marisa Quiñones', 'Multimedia Manager, Webmaster'],
        ['Joseph Moore', 'Aerospace Division Lead'],
        ['Colton Daigneault', 'Inventory Manager'],
        ['Jonathan He', 'Event Coordinator'],
        ['Isaac Choi', 'Social Media Manager'],
        ['Jay Kim', 'Sponsorship Coordinator'],
        ['Tommie Snow', 'Supporting Officer'],
    ],
    '2023 – 2024' => [
        ['Nova Lader', 'Co-President'],
    ],
    '2024 – 2025' => [
        ['Laurance “Murphy” Boyd', 'Vice President'],
        ['Mahathi Sriji', 'Deputy Financial Director, Inventory Manager'],
        ['Brielle Brown', 'Social Media Manager, Multimedia Manager'],
        ['Sophia Casas', 'Corporate Relations, Sponsorship Coordinator'],
        ['Farhan Ar Rafi', 'Project Manager'],
        ['Jaclyn “Jaci” Martinez', 'Food Coordinator'],
        ['Jayden McDaniel', 'Competition Team Lead'],
    ],
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
            <div class="shell">
                <div class="range justify-center">
                    <div class="cell-lg-8 text-center">
                        <h1>Our Alumni</h1>
                        <h6>These people are the ones responsible for building our organisation and getting us to where we are today.</h6>
                        <p>Past officers by the year they joined the team. Current officers are on <a href="/about/our-team">Our Team</a>.</p>
                    </div>
                </div>
                <?php foreach ($alumni_by_year as $year => $people): ?>
                    <div class="offset-top-66">
                        <h3><?php echo $year; ?></h3>
                        <hr class="divider-color-2">
                        <div class="range range-30 text-left">
                            <?php foreach ($people as [$name, $roles]): ?>
                                <div class="cell-sm-6 cell-lg-4 offset-top-30">
                                    <p style="margin-bottom: 2px;"><strong><?php echo htmlspecialchars($name, ENT_QUOTES); ?></strong></p>
                                    <p class="small text-silver-chalice"><?php echo htmlspecialchars($roles, ENT_QUOTES); ?></p>
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
