<?php
require('template/top.php');
head('Our History', true);

/*
 * Club history page (URW-80). SHELL BUILD.
 *
 * The 2019->today timeline below is sourced from the UNT Robotics Discord
 * archive (243k-message scrape, findings/club-history.md) and is well
 * documented. Photos are the already-optimized set under /images/content/.
 *
 * PENDING (pre-2018 layer — deliberately framed, not fabricated):
 *   - The "roots since the 1970s" claim rests on a photo the founder recalls
 *     seeing; it is NOT yet located in an archive. digital.library.unt.edu
 *     (The Yucca / The Aerie yearbooks) is the place to find it but blocks
 *     automation — needs a manual search or a UNT Special Collections request.
 *   - Charles Bido (pre-2018 club lead) — real UNT alum; club role/dates
 *     unconfirmed by any public source.
 *   - Advisors "Jack & Suzie Sprague" — founder-attested; spelling of "Suzie"
 *     unconfirmed; no external source found yet.
 * The Origins section is a marked fill-in block until the above firm up.
 */

// Brand green that passes contrast (matches css/accessibility.css overrides).
$eras = array(
    array(
        'year' => '2019',
        'title' => 'A new chapter: “UNT Robotics &amp; Aerospace”',
        'body' => "The club&rsquo;s modern era begins. The Discord that still runs things today went up in <strong>October 2019</strong>, growing out of a student rocketry effort that joined forces with UNT&rsquo;s existing robotics group. Members designed the first logo and set their sights on NASA Student Launch and intercollegiate rocketry. The competition team took <strong>first place at IEEE Region 5</strong> with an autonomous balloon-popping drone, and the very first <strong>Botathon</strong> ran that spring, robots carrying balloons on their backs and popping everyone else&rsquo;s.",
        'img' => 'aerospace/rocket-launch-still.jpg',
        'gallery' => array('ieee2019/build-5.jpg', 'ieee2019/build-3.jpg'),
    ),
    array(
        'year' => '2020',
        'title' => 'Divisions, and a pivot to virtual',
        'body' => "A roles-and-divisions system let members pick the teams they wanted to work on. An aerospace and space community formed over the summer, and the <strong>3D-printing</strong> channel that still anchors half the club&rsquo;s builds got going that fall. Sofabot, the rideable robot couch, moved under its own power for the first time. Then COVID-19 closed campus. Botathon 2020 had to be cancelled, but the club kept building online through Zoom and Discord, running CAD and OpenRocket workshops and fielding a VEX&nbsp;U team.",
        'img' => 'printing/printer-in-action.jpg',
        'gallery' => array('events/meeting-pics.jpg', 'sofabot/early-build.jpg'),
    ),
    array(
        'year' => '2021',
        'title' => 'The biggest growth surge on record',
        'body' => "Campus reopened and membership exploded, with <strong>153 new members in a single month</strong> (September 2021), the biggest recruiting spike in club history. In April the club spun up a dedicated <strong>High-Power Rocketry team</strong> to enter NASA Student Launch, backed by sponsors including RESPEC, Mouser, and O&rsquo;Reilly. The <strong>NASA JPL Open-Source Rover</strong> started in October, first as a rocket payload, then as a build of its own. Members competed at HackDFW in Frisco, ran Botathon Season 3 (Pirates), built an eight-foot rocket-shaped trophy case, and teamed up with UNT Fashion Design students on custom club apparel.",
        'img' => 'aerospace/hpr-launch-prep.jpg',
        'gallery' => array('botathon/s3-1.jpg', 'botathon/s3-2.jpg', 'rover/laser-cut-parts.jpg'),
    ),
    array(
        'year' => '2022',
        'title' => 'Rockets, rovers, and a bigger Botathon',
        'body' => "The rocketry team flew hard. After a subscale flight to <strong>4,588 feet</strong>, they took their full-scale rocket &ldquo;Sparrowhawk&rdquo; to competition, lost it to a launch-pad anomaly, and turned straight around to start Season 2 with a new camera payload. Rover build nights filled the workshop, the club shipped its automated dues and &ldquo;Good Standing&rdquo; system on untrobotics.com, and an Arduino wind-turbine build went to HackUNT. Botathon Season 4 (Football) debuted the club&rsquo;s own Xbox-controller driver and live 3D printing during the event. Members also ran a model-rocket day for about 40 scouts, and Sofabot came roaring back after two years off.",
        'img' => 'aerospace/nasa-sl-2022-launch.jpg',
        'gallery' => array('rover/frame-assembly.jpg', 'outreach/scouts-stem.jpg'),
    ),
    array(
        'year' => '2023',
        'title' => 'To Huntsville and back',
        'body' => "The rocketry team&rsquo;s biggest year. After passing NASA&rsquo;s design reviews, they traveled to <strong>Marshall Space Flight Center in Huntsville, Alabama</strong> in April to fly &ldquo;Phoenix,&rdquo; a 9.3-foot rocket carrying a Raspberry-Pi camera-and-radio payload. Members visited the real Open-Source Rover at NASA JPL that summer. Botathon Season 5 (Mario Kart) lit up the Discovery Park hallway, projects went to Senior Design Day, and the club ran STEM events for hundreds of K-12 students across the metroplex. Workshops covered KiCad PCB design with a professional guest, GPS and radio programming, and Python.",
        'img' => 'aerospace/nasa-sl-2023-1.jpg',
        'gallery' => array('rover/system-integration.jpg', 'aerospace/hpr-custom-paint.jpg'),
    ),
    array(
        'year' => '2024',
        'title' => 'A new flagship, and a competition team',
        'body' => "Members voted in a new flagship robot: <strong>Scrapp-E</strong>, a tracked companion bot with an animatronic head and modular arms, themed after Scrappy the eagle. A full-size wooden mock-up was standing by December. A competition team, the &ldquo;Scrapaholics,&rdquo; formed to enter DJI RoboMaster. Weekend sessions pushed Sofabot&rsquo;s drivetrain and steering toward finished, the rover&rsquo;s software effort restarted on ROS&nbsp;2, and members volunteered with robotics students at a local high school. Botathon Season 6 was Capture the Flag.",
        'img' => 'scrappe/build-hdr.jpg',
        'gallery' => array('sofabot/build-1.jpg', 'sofabot/circle-done.jpg'),
    ),
    array(
        'year' => '2025',
        'title' => 'The rover drives, and Botathon gets tactical',
        'body' => "The JPL Rover moved onto a Raspberry Pi&nbsp;4 running <strong>ROS&nbsp;2</strong> with RoboClaw controllers, with build days every Friday. Scrapp-E&rsquo;s chassis and drive parts came together through the spring. Botathon Season 7 (&ldquo;Keep on Trucking&rdquo;) added an infrared laser-blaster mechanic, where bots lose lives when they&rsquo;re hit and have to return to a station to recharge. The competition team shifted its near-term target from RoboMaster to the IEEE Region 5 autonomous challenge after DJI parts got caught up in tariffs. The club also started working alongside Engineers United, and picked up an old vending machine to refurbish.",
        'img' => 'botathon/s7-1.jpg',
        'gallery' => array('rover/bench-work.jpg', 'scrappe/first-chassis-mount.jpg'),
    ),
    array(
        'year' => '2026',
        'title' => 'Still building',
        'body' => "Scrapp-E&rsquo;s 3D-printed gearbox drove the tracks for the first time, and a working animatronic hand joined it. The club&rsquo;s large-format Modix printer ran its first test print, a from-scratch arcade claw machine went into CAD, and a filament-recycling project got funded. The refurbished vending machine powered on and got stocked with the lab-day essentials students always forget. Botathon Season 8 is set for March, and the workshop lights are still on.",
        'img' => 'scrappe/build-progress.jpg',
        'gallery' => array('printing/prusa-xl-print.jpg', 'rover/raspberry-pi-wiring.jpg'),
    ),
);
?>
<style>
    .hist-hero { padding: 60px 0 6px; text-align: center; }
    .hist-hero h1 { margin-bottom: 10px; }
    .hist-hero p { max-width: 720px; margin: 0 auto; color: #555; font-size: 17px; line-height: 1.6; }

    /* Origins — the pre-2018 layer, honestly framed while sources firm up. */
    .hist-origins { max-width: 860px; margin: 34px auto 0; padding: 0 15px; }
    .hist-origins .card { background: #f4f8f5; border: 1px solid #d8e6dd; border-left: 4px solid #157a3f; border-radius: 10px; padding: 26px 28px; }
    .hist-origins h2 { margin: 0 0 12px; }
    .hist-origins p { color: #3a403c; line-height: 1.7; font-size: 16px; }
    .hist-origins p + p { margin-top: 12px; }
    .hist-origins .founders { font-weight: 600; color: #157a3f; }
    .hist-fillin { margin-top: 16px; font-size: 14px; color: #5f6360; background: #fff; border: 1px dashed #bcd3c4; border-radius: 8px; padding: 12px 14px; }
    .hist-fillin strong { color: #157a3f; }

    /* Timeline */
    .hist-timeline-head { max-width: 1080px; margin: 44px auto 0; padding: 0 15px; text-align: center; }
    .hist-timeline { list-style: none; max-width: 960px; margin: 8px auto 0; padding: 20px 15px 10px; position: relative; }
    .hist-timeline::before { content: ""; position: absolute; left: 92px; top: 20px; bottom: 20px; width: 2px; background: #d8e6dd; }
    .hist-era { position: relative; display: flex; gap: 26px; align-items: flex-start; margin-bottom: 46px; }
    .hist-year { flex: 0 0 68px; text-align: right; }
    .hist-year .badge { display: inline-block; background: #157a3f; color: #fff; font-weight: 700; font-size: 15px; padding: 4px 10px; border-radius: 6px; }
    .hist-dot { position: absolute; left: 86px; top: 6px; width: 14px; height: 14px; background: #fff; border: 3px solid #157a3f; border-radius: 50%; z-index: 1; }
    .hist-body { flex: 1 1 auto; padding-left: 22px; }
    .hist-body h3 { margin: 0 0 10px; font-size: 22px; }
    .hist-body p { color: #444; line-height: 1.7; font-size: 16px; margin: 0; }
    .hist-media { margin-top: 14px; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px; }
    .hist-media img { width: 100%; height: 150px; object-fit: cover; border-radius: 8px; display: block; box-shadow: 0 4px 16px rgba(0,0,0,.10); cursor: zoom-in; margin: 0 !important; transition: transform .15s, opacity .15s; }
    .hist-media img:hover { opacity: .9; transform: translateY(-2px); }

    @media (max-width: 767px) {
        .hist-timeline::before { left: 8px; }
        .hist-era { gap: 12px; flex-direction: column; }
        .hist-year { flex-basis: auto; text-align: left; padding-left: 26px; }
        .hist-dot { left: 2px; top: 4px; }
        .hist-body { padding-left: 26px; }
    }

    .hist-people { max-width: 900px; margin: 20px auto 0; padding: 0 15px; }
    .hist-people h2 { text-align: center; margin-bottom: 8px; }
    .hist-people p { color: #444; line-height: 1.7; font-size: 16px; text-align: center; max-width: 720px; margin: 0 auto; }
    .hist-cta { text-align: center; padding: 40px 0 54px; }
</style>
<main id="main-content" class="page-content">
    <section class="breadcrumb-classic">
      <div class="rd-parallax">
        <div data-speed="0.25" data-type="media" data-url="/images/headers/about.jpg" class="rd-parallax-layer"></div>
        <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
          <div class="shell">
            <ul class="list-breadcrumb">
              <li><a href="/">Home</a></li>
              <li>History</li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <section class="hist-hero">
        <div class="shell">
            <h1>Our History</h1>
            <p>UNT Robotics was revived in 2018, but robotics and engineering culture at North Texas runs much deeper. Here&rsquo;s the story we&rsquo;ve been able to piece together.</p>
        </div>
    </section>

    <section class="hist-origins">
        <div class="card">
            <h2>Origins</h2>
            <p>Robotics and engineering have a long history at the University of North Texas. Student teams and clubs have come and gone on campus for decades, <strong>reportedly as far back as the 1970s</strong>, through the university&rsquo;s earlier eras as North Texas State University and the growth of its computing and engineering programs.</p>
            <p>By the mid-2010s the organization had gone quiet. In <strong>2018</strong> it was revived by <span class="founders">Sebastian King and Nick Tindle</span> as a fresh iteration of UNT Robotics, picking up a torch that had been carried before them, including by earlier leaders such as Charles&nbsp;Bido.</p>
            <!--
              FILL-IN once sourced (see file header): confirm the 1970s photo +
              citation, Charles Bido's club role/dates, and the pre-2018 division
              history (aerospace/rocketry). Then expand this section with real
              dates, names, and an archival image.
            -->
            <div class="hist-fillin">
                <strong>This section is still growing.</strong> We&rsquo;re digging through the UNT archives for the club&rsquo;s earliest days. If you have old photos or stories, or know who led robotics at North Texas before 2018, we&rsquo;d love to hear from you.
            </div>
        </div>
    </section>

    <section class="hist-timeline-head">
        <h2>The modern era</h2>
    </section>
    <ol class="hist-timeline">
        <?php foreach ($eras as $e): ?>
            <li class="hist-era">
                <span class="hist-dot" aria-hidden="true"></span>
                <div class="hist-year"><span class="badge"><?php echo htmlspecialchars($e['year']); ?></span></div>
                <div class="hist-body">
                    <h3><?php echo $e['title']; ?></h3>
                    <p><?php echo $e['body']; ?></p>
                    <?php $imgs = array_merge(!empty($e['img']) ? array($e['img']) : array(), !empty($e['gallery']) ? $e['gallery'] : array()); ?>
                    <?php if ($imgs): ?>
                        <div class="hist-media">
                            <?php foreach ($imgs as $g): ?>
                                <img src="/images/content/<?php echo htmlspecialchars($g); ?>" alt="UNT Robotics, <?php echo htmlspecialchars($e['year']); ?>" loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ol>

    <section class="hist-people">
        <h2>People who shaped the club</h2>
        <p>UNT Robotics runs on its members and an elected officer team. Faculty advisors and mentors have helped along the way, among them Jack and Suzie&nbsp;Sprague, Dr.&nbsp;Keathly, Dr.&nbsp;Wasikowski, and Dr.&nbsp;Hassan. Guest speakers have included NASA engineer George&nbsp;Salazar, Dr.&nbsp;Amir&nbsp;Jafari of UNT&rsquo;s Advanced Robotics Manipulators Lab, and roboticist Terrence&nbsp;Southern.</p>
        <!-- Advisor names incl. Sprague are founder-attested; confirm "Suzie" spelling + roles/dates before treating as fully verified. -->
    </section>

    <div class="hist-cta">
        <a href="/join/discord" class="btn btn-primary">Be part of the next chapter</a>
    </div>
</main>

<div id="lightbox" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.9);align-items:center;justify-content:center;cursor:zoom-out;">
    <img id="lightbox-img" src="" alt="" style="max-width:92vw;max-height:92vh;border-radius:6px;box-shadow:0 10px 40px rgba(0,0,0,.5);">
</div>
<script>
(function () {
    var lb = document.getElementById('lightbox'), lbImg = document.getElementById('lightbox-img');
    document.querySelectorAll('.hist-media img').forEach(function (img) {
        img.addEventListener('click', function () {
            lbImg.setAttribute('src', img.getAttribute('src'));
            lb.style.display = 'flex';
        });
    });
    lb.addEventListener('click', function () { lb.style.display = 'none'; lbImg.setAttribute('src', ''); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { lb.style.display = 'none'; } });
})();
</script>
<?php
footer();
