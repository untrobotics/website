<?php
require('template/top.php');
head('Activities', true);
?>

       <main class="page-content">
        <!-- Classic Breadcrumbs-->
        <section class="breadcrumb-classic">
          <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/headers/activities.jpg" class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
              <div class="shell">
                <ul class="list-breadcrumb">
                  <li><a href="/">Home</a></li>
                  <li><a href="/about">About</a></li>
                  <li>Activities</li>
                </ul>
              </div>
            </div>
          </div>
        </section>

        <style>
            .act-wrap { max-width: 1040px; margin: 0 auto; padding: 0 16px; }
            .act-hero { text-align: center; padding: 6px 0 0; }
            .act-kicker { display: inline-block; font-size: 12px; letter-spacing: .14em; text-transform: uppercase; color: #24c57c; font-weight: 700; margin-bottom: 6px; }
            .act-hero h2 { margin: 0; }
            .act-lead { max-width: 760px; margin: 14px auto 0; color: #555; font-size: 17px; line-height: 1.7; }
            .act-lead strong { color: #2a2a2a; }
            /* Image strip */
            .act-strip { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 30px 0 6px; }
            .act-strip img { width: 100%; height: 200px; object-fit: cover; border-radius: 10px; margin: 0 !important; box-shadow: 0 4px 16px rgba(0,0,0,.1); display: block; }
            /* Sections */
            .act-section { margin-top: 58px; }
            .act-head { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
            .act-head .icon { color: #24c57c; font-size: 30px; line-height: 1; }
            .act-head h3 { margin: 0; font-size: 23px; white-space: nowrap; }
            .act-head .rule { flex: 1; height: 1px; background: linear-gradient(to right, #d9e7e0, rgba(217,231,224,0)); }
            .act-text { color: #454545; line-height: 1.75; font-size: 16px; }
            .act-text + .act-text { margin-top: 12px; }
            /* Chips */
            .act-subtle { font-size: 13px; text-transform: uppercase; letter-spacing: .06em; color: #8a8a8a; font-weight: 700; margin: 20px 0 10px; }
            .chips { display: flex; flex-wrap: wrap; gap: 8px; }
            .chip { background: #f2f7f4; border: 1px solid #dcebe3; color: #1f6b46; border-radius: 999px; padding: 6px 14px; font-size: 13.5px; font-weight: 600; }
            /* Competition cards */
            .comp-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .comp-card { border: 1px solid #ececec; border-radius: 12px; padding: 22px 24px; background: #fff; box-shadow: 0 3px 14px rgba(0,0,0,.05); position: relative; display: flex; flex-direction: column; }
            .comp-card .tag { font-size: 11px; letter-spacing: .08em; text-transform: uppercase; color: #9aa0a6; font-weight: 700; margin-bottom: 8px; }
            .comp-card h4 { margin: 0 0 8px; font-size: 19px; }
            .comp-card p { color: #555; font-size: 15px; line-height: 1.65; margin: 0; }
            .comp-card a { color: #24c57c; font-weight: 600; }
            .comp-card.win { border-color: #24c57c; box-shadow: 0 6px 22px rgba(36,197,124,.16); }
            .comp-card .badge { position: absolute; top: 16px; right: 16px; background: #24c57c; color: #fff; font-size: 11px; font-weight: 700; letter-spacing: .05em; padding: 4px 10px; border-radius: 999px; text-transform: uppercase; }
            /* Collaborations */
            .collab-list { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
            .collab-list li { padding: 16px 18px; background: #fafbfa; border: 1px solid #ececec; border-radius: 10px; }
            .collab-list .org { display: block; color: #1f6b46; font-weight: 700; font-size: 15px; margin-bottom: 4px; }
            .collab-list p { margin: 0; color: #555; font-size: 14.5px; line-height: 1.55; }
            .act-cta { text-align: center; padding: 46px 0 10px; }
            @media (max-width: 767px) {
                .act-strip, .comp-grid, .collab-list { grid-template-columns: 1fr; }
                .act-head h3 { white-space: normal; }
            }
        </style>

        <section class="section-50 section-md-75">
          <div class="act-wrap">

            <div class="act-hero">
                <span class="act-kicker">Robotics for everyone</span>
                <h2>What We Do</h2>
                <p class="act-lead">UNT Robotics is a student-run engineering organization at the University of North Texas with roots going back decades on campus. After a period of dormancy, the club was <strong>revived in 2018</strong> and has grown ever since. Our mission is simple: inspire people and teach them the skills to reach their goals in robotics &mdash; and everything we do is open to <strong>every major and every skill level</strong>, from first-time beginners to seasoned builders.</p>
            </div>

            <div class="act-strip">
                <img src="/images/content/events/hackunt-2024-1.jpg" alt="Members at an event" loading="lazy">
                <img src="/images/content/aerospace/hpr-launch-prep.jpg" alt="High-power rocket" loading="lazy">
                <img src="/images/content/rover/system-integration.jpg" alt="Rover build" loading="lazy">
            </div>

            <!-- Workshops -->
            <div class="act-section">
                <div class="act-head">
                    <span class="icon thin-icon-study"></span>
                    <h3>Workshops &amp; Learning</h3>
                    <span class="rule"></span>
                </div>
                <p class="act-text">We host hands-on workshops throughout the semester that take members from the fundamentals all the way to advanced builds &mdash; electrical circuits, programming microcontrollers, computer-aided design (CAD), image processing, and using Bluetooth to control robots. They&rsquo;re beginner-friendly and open to all, no experience required.</p>
                <p class="act-text">We also bring industry into the room, hosting talks and demos from companies chosen to reflect the mechanical, biomedical, and computer-science backgrounds of our members:</p>
                <div class="chips" style="margin-top:14px;">
                    <span class="chip">L3</span>
                    <span class="chip">RoboKind</span>
                    <span class="chip">StandardUser CyberSecurity</span>
                    <span class="chip">Bell</span>
                </div>
            </div>

            <!-- Competitions -->
            <div class="act-section">
                <div class="act-head">
                    <span class="icon thin-icon-chart"></span>
                    <h3>Competitions</h3>
                    <span class="rule"></span>
                </div>
                <div class="comp-grid">
                    <div class="comp-card win">
                        <span class="badge">1st Place</span>
                        <span class="tag">IEEE Region 5</span>
                        <h4>IEEE Region 5 Robotics</h4>
                        <p>Our IEEE Robotics &amp; Automation Society team took <strong>first place</strong> against eleven other universities for a $1,200 prize &mdash; programming a drone to autonomously identify balloons by color with computer vision, fly to them, and pop them in a judge-determined order, entirely pre-programmed with no human input once it launched.</p>
                    </div>
                    <div class="comp-card">
                        <span class="tag">Our own event &middot; since 2019</span>
                        <h4>Botathon</h4>
                        <p><a href="/botathon">Botathon</a> is our annual cross-disciplinary robotics competition, hosted by students with a fresh theme every year. It&rsquo;s built as an approachable, hands-on introduction to robotics &mdash; anyone can enter, learn, and build something real over the course of the event.</p>
                    </div>
                    <div class="comp-card">
                        <span class="tag">Autonomous robotics</span>
                        <h4>IEEE Autonomous Rover</h4>
                        <p>Our collegiate team competes in the IEEE Region 5 Autonomous Rover competition, where we&rsquo;ve been commended for our mechanical-engineering prowess.</p>
                    </div>
                    <div class="comp-card">
                        <span class="tag">High-power rocketry</span>
                        <h4>NASA Student Launch</h4>
                        <p>Members have represented UNT on a NASA Student Launch team &mdash; designing, building, and flying a high-power rocket as part of NASA&rsquo;s national engineering challenge.</p>
                    </div>
                </div>
            </div>

            <!-- Recreational -->
            <div class="act-section">
                <div class="act-head">
                    <span class="icon thin-icon-car"></span>
                    <h3>Recreational Projects</h3>
                    <span class="rule"></span>
                </div>
                <p class="act-text">Not every build is a competition. Our recreational projects are for anyone who just wants to make something cool with a team &mdash; there&rsquo;s no time commitment, so people come and go as they like. Anyone can pitch a project; if you can rally a team, we&rsquo;ll help you build it.</p>
                <div class="act-subtle">Past &amp; ongoing builds</div>
                <div class="chips">
                    <span class="chip">Sofabot &mdash; self-driving sofa</span>
                    <span class="chip">SAE Racecar Dashboard</span>
                    <span class="chip">T-shirt Cannon Bot</span>
                    <span class="chip">Quadcopters</span>
                </div>
            </div>

            <!-- Collaborations -->
            <div class="act-section">
                <div class="act-head">
                    <span class="icon thin-icon-pointer"></span>
                    <h3>Working With Others</h3>
                    <span class="rule"></span>
                </div>
                <p class="act-text">We collaborate across campus and the community:</p>
                <ul class="collab-list" style="margin-top:16px;">
                    <li>
                        <span class="org">Society of Automotive Engineers (SAE)</span>
                        <p>Built the dashboard and helped with the wiring harness for their competition racecar.</p>
                    </li>
                    <li>
                        <span class="org">American Society of Mechanical Engineers (ASME)</span>
                        <p>Co-hosted a talk featuring Bell (formerly Bell Helicopter).</p>
                    </li>
                    <li>
                        <span class="org">Engineering United</span>
                        <p>Provided volunteers and members for HackUNT, and ran a session on how to win a hackathon.</p>
                    </li>
                    <li>
                        <span class="org">Kappa Delta Pi</span>
                        <p>Partnered with this education honor society on a STEM workshop for future teachers &mdash; programming with Scratch, gear ratios with LEGO, and computer design through skits.</p>
                    </li>
                </ul>
            </div>

            <div class="act-cta">
                <a href="/join/discord" class="btn btn-primary">Join us on Discord</a>
            </div>

          </div>
        </section>

</main>

<?php
footer();
?>
