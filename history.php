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
        'body' => "The club&rsquo;s modern era begins. Its Discord &mdash; still the organizational backbone today &mdash; was created in <strong>October 2019</strong>, growing out of a student rocketry effort that joined forces with UNT&rsquo;s existing robotics group. Members designed the first logo and set their sights on big goals: NASA Student Launch and intercollegiate rocketry. That same year, the competition team took <strong>first place at IEEE Region 5</strong> with an autonomous balloon-popping drone.",
        'img' => 'aerospace/rocket-launch-still.jpg',
        'gallery' => array('ieee2019/build-5.jpg'),
    ),
    array(
        'year' => '2020',
        'title' => 'Divisions, and a pivot to virtual',
        'body' => "A roles-and-divisions system launched so members could self-select the project teams they wanted &mdash; rocketry, robotics, and more. When COVID-19 closed campus, the club kept building online through Zoom and Discord, running CAD and OpenRocket workshops and fielding a VEX&nbsp;U competition team.",
        'img' => 'events/meeting-pics.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2021',
        'title' => 'The biggest growth surge on record',
        'body' => "As campus reopened, membership exploded &mdash; <strong>153 new members in a single month</strong> (September 2021), the largest recruiting spike in the club&rsquo;s history. Officers tabled at Discovery Park, ran the first full slate of officer elections, and moved general meetings back in person.",
        'img' => 'events/group-work-session.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2022',
        'title' => 'Botathon becomes the flagship &mdash; and the Rover begins',
        'body' => "<strong>Botathon</strong>, the club&rsquo;s free, all-majors robot competition, grew into its signature annual event at Discovery Park. The club built the automated dues and &ldquo;Good Standing&rdquo; system on untrobotics.com, kicked off the <strong>NASA JPL Open-Source Rover</strong> project, and showed off an Arduino wind-turbine build at HackUNT.",
        'img' => 'rover/frame-assembly.jpg',
        'gallery' => array('botathon/s3-1.jpg', 'botathon/s3-2.jpg'),
    ),
    array(
        'year' => '2023',
        'title' => 'Workshops mature',
        'body' => "The 4th annual Botathon ran a Mario-Kart-themed robot race, and club projects were featured at Senior Design Day. Members ran a deep slate of workshops &mdash; KiCad PCB design with a professional guest, GPS/radio programming, NLP in Python, and drones &amp; deep learning.",
        'img' => 'rover/system-integration.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2024',
        'title' => 'Sofabot, Scrapp-E, and RoboMasters',
        'body' => "The club voted to enter <strong>RoboMasters</strong>, the international collegiate robotics competition. Weekend build sessions brought the rideable self-driving <strong>Sofabot</strong> to life, and a brand-new flagship robot &mdash; <strong>Scrapp-E</strong>, the club mascot &mdash; began taking shape. Guest speakers included NASA figures and Dr.&nbsp;Amir Jafari of UNT&rsquo;s Advanced Robotics Manipulators Lab.",
        'img' => 'scrappe/build-hdr.jpg',
        'gallery' => array('sofabot/build-1.jpg', 'sofabot/circle-done.jpg'),
    ),
    array(
        'year' => '2025',
        'title' => 'The Rover goes ROS 2',
        'body' => "The JPL Rover was re-platformed onto a Raspberry Pi&nbsp;4 running <strong>ROS&nbsp;2</strong> with RoboClaw motor controllers, with weekly Friday build days. Scrapp-E work continued, and the club began an ongoing collaboration with Engineers United at Discovery Park.",
        'img' => 'rover/bench-work.jpg',
        'gallery' => array(),
    ),
    array(
        'year' => '2026',
        'title' => 'Still building',
        'body' => "The club tabled at Spring Fling, ran a semester of structured intro projects &mdash; an Arduino piano, a 3D-printed robotic arm and quadruped &mdash; and began prep for the next Botathon. Seven seasons of Botathon in, and the workshop lights are still on.",
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
            <p>UNT Robotics was revived in 2018 &mdash; but robotics and engineering culture at North Texas runs much deeper. Here&rsquo;s the story we&rsquo;ve been able to piece together.</p>
        </div>
    </section>

    <section class="hist-origins">
        <div class="card">
            <h2>Origins</h2>
            <p>Robotics and engineering have a long history at the University of North Texas. Student teams and clubs have come and gone on campus for decades &mdash; <strong>reportedly as far back as the 1970s</strong> &mdash; through the university&rsquo;s earlier eras as North Texas State University and the growth of its computing and engineering programs.</p>
            <p>By the mid-2010s the organization had gone quiet. In <strong>2018</strong> it was revived by <span class="founders">Sebastian King and Nick Tindle</span> as a fresh iteration of UNT Robotics &mdash; picking up a torch that had been carried before them, including by earlier leaders such as Charles&nbsp;Bido.</p>
            <!--
              FILL-IN once sourced (see file header): confirm the 1970s photo +
              citation, Charles Bido's club role/dates, and the pre-2018 division
              history (aerospace/rocketry). Then expand this section with real
              dates, names, and an archival image.
            -->
            <div class="hist-fillin">
                <strong>This section is still growing.</strong> We&rsquo;re digging through the UNT archives for the club&rsquo;s earliest days. If you have old photos, stories, or know who led robotics at North Texas before 2018, we&rsquo;d love to hear from you.
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
        <p>UNT Robotics runs on its members and an elected officer team, supported over the years by faculty advisors and mentors &mdash; including Jack and Suzie&nbsp;Sprague, Dr.&nbsp;Keathly, Dr.&nbsp;Wasikowski, and Dr.&nbsp;Hassan &mdash; and guest speakers such as NASA engineer George&nbsp;Salazar, Dr.&nbsp;Amir&nbsp;Jafari of UNT&rsquo;s Advanced Robotics Manipulators Lab, and roboticist Terrence&nbsp;Southern.</p>
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
