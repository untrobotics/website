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
        'title' => '“UNT Robotics &amp; Aerospace”',
        'body' => "The Discord server that still runs the club today was set up in <strong>October 2019</strong>. It grew out of a student rocketry effort that teamed up with UNT&rsquo;s existing robotics group. Members drew the first logo and set targets: NASA Student Launch and intercollegiate rocketry. The competition team won <strong>first place at IEEE Region 5</strong> that year, with a drone that found and popped balloons by color.",
        'img' => 'aerospace/rocket-launch-still.jpg',
        'gallery' => array('ieee2019/build-5.jpg'),
    ),
    array(
        'year' => '2020',
        'title' => 'Divisions, then a move online',
        'body' => "Members set up a roles system so people could pick the project teams they cared about. When COVID-19 closed campus, the club moved online. It ran CAD and OpenRocket workshops over Zoom and Discord and put together a VEX&nbsp;U team.",
        'img' => 'events/meeting-pics.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2021',
        'title' => 'The biggest month on record',
        'body' => "Membership jumped once campus reopened. <strong>153 people joined in September 2021</strong>, the biggest single month the club has had. Officers ran the first full election, tabled at Discovery Park, and brought general meetings back in person.",
        'img' => 'events/group-work-session.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2022',
        'title' => 'Botathon becomes the main event, and the Rover starts',
        'body' => "<strong>Botathon</strong>, the club&rsquo;s free robot competition open to any major, became the event the year is built around. The club also wrote the automated dues and &ldquo;Good Standing&rdquo; system on untrobotics.com, started the <strong>NASA JPL Open-Source Rover</strong>, and showed an Arduino wind-turbine build at HackUNT.",
        'img' => 'rover/frame-assembly.jpg',
        'gallery' => array('botathon/s3-1.jpg', 'botathon/s3-2.jpg'),
    ),
    array(
        'year' => '2023',
        'title' => 'Workshops mature',
        'body' => "The fourth Botathon ran as a Mario-Kart-themed race, and club projects went to Senior Design Day. Members ran workshops through the year on KiCad PCB design (with a professional guest), GPS and radio programming, Python NLP, and drones.",
        'img' => 'rover/system-integration.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2024',
        'title' => 'Scrapp-E begins, and the RoboMasters vote',
        'body' => "The club voted to enter <strong>RoboMasters</strong>, an international collegiate competition. Weekend sessions got the rideable, self-driving <strong>Sofabot</strong> working, and a new flagship robot started coming together: <strong>Scrapp-E</strong>, based on the club mascot. Guest speakers that year came from NASA and UNT&rsquo;s robotics labs.",
        'img' => 'scrappe/build-hdr.jpg',
        'gallery' => array('sofabot/build-1.jpg', 'sofabot/circle-done.jpg'),
    ),
    array(
        'year' => '2025',
        'title' => 'The Rover goes ROS 2',
        'body' => "The JPL Rover moved onto a Raspberry Pi&nbsp;4 running <strong>ROS&nbsp;2</strong> with RoboClaw motor controllers, with build days every Friday. Scrapp-E work kept going, and the club started working alongside Engineers United at Discovery Park.",
        'img' => 'rover/bench-work.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2026',
        'title' => 'Still building',
        'body' => "The club tabled at Spring Fling and ran a semester of intro builds: an Arduino piano, a 3D-printed robotic arm, a printed quadruped. Prep for the eighth Botathon is underway.",
        'img' => 'botathon/s7-1.jpg',
        'gallery' => array('botathon/s7-2.jpg'),
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
            <p>UNT Robotics was revived in 2018. Robotics at North Texas goes back a lot further than that, and we&rsquo;re still piecing the early years together.</p>
        </div>
    </section>

    <section class="hist-origins">
        <div class="card">
            <h2>Origins</h2>
            <p>Students have built robots and rockets at North Texas for a long time. Clubs have formed and faded here since <strong>at least the 1970s</strong>, back through the university&rsquo;s years as North Texas State and the early growth of its computing and engineering programs.</p>
            <p>The organization had gone quiet by the mid-2010s. <span class="founders">Sebastian King and Nick Tindle</span> restarted it in <strong>2018</strong> as a new version of UNT Robotics. Charles&nbsp;Bido had run an earlier version before them.</p>
            <!--
              FILL-IN once sourced (see file header): confirm the 1970s photo +
              citation, Charles Bido's club role/dates, and the pre-2018 division
              history (aerospace/rocketry). Then expand this section with real
              dates, names, and an archival image.
            -->
            <div class="hist-fillin">
                <strong>This part is still growing.</strong> We&rsquo;re going through the UNT archives to fill in the club&rsquo;s early days. If you have old photos or stories, or you know who ran robotics at North Texas before 2018, get in touch.
            </div>
        </div>
    </section>

    <section class="hist-timeline-head">
        <h2>2019 to now</h2>
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
        <h2>People behind the club</h2>
        <p>UNT Robotics runs on its members and an elected officer team. Faculty advisors and mentors have helped along the way, among them Jack and Suzie&nbsp;Sprague, Dr.&nbsp;Keathly, Dr.&nbsp;Wasikowski, and Dr.&nbsp;Hassan. Guest speakers have included NASA engineer George&nbsp;Salazar, Dr.&nbsp;Amir&nbsp;Jafari of UNT&rsquo;s Advanced Robotics Manipulators Lab, and roboticist Terrence&nbsp;Southern.</p>
        <!-- Advisor names incl. Sprague are founder-attested; confirm "Suzie" spelling + roles/dates before treating as fully verified. -->
    </section>

    <div class="hist-cta">
        <a href="/join/discord" class="btn btn-primary">Come build with us</a>
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
