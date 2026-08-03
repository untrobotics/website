<?php
require('../template/top.php');
head('Sponsorship Flyer', true);
?>
<style>
    /* ---- UNT Robotics sponsorship packet -------------------------------------
       One source, two outputs: a live web page, and (via print CSS) a clean
       multi-page PDF. Print rules at the bottom drop the site chrome and lay the
       sections out on pages. Palette: UNT green + bright accent, gold for the
       premium tier, warm-neutral ground. ------------------------------------- */
    .sp { --green:#00853e; --bright:#24c57c; --ink:#171a18; --muted:#5c625e;
          --ground:#f6f8f6; --line:#e5eae7; --gold:#b8912e;
          color:var(--ink); font-family:'Poppins',sans-serif; }
    .sp * { box-sizing:border-box; }
    .sp-wrap { max-width:1060px; margin:0 auto; padding:0 22px; }
    .sp h1,.sp h2,.sp h3,.sp h4 { line-height:1.12; margin:0; }
    .sp p { line-height:1.65; margin:0; }
    .sp .kick { display:inline-block; font-size:12px; letter-spacing:.18em; text-transform:uppercase; font-weight:700; color:var(--bright); }

    /* Cover */
    .sp-cover { position:relative; min-height:520px; display:flex; align-items:flex-end; color:#fff; overflow:hidden; }
    .sp-cover img.bg { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
    .sp-cover .veil { position:absolute; inset:0; background:linear-gradient(180deg, rgba(10,25,16,.35) 0%, rgba(8,20,12,.82) 78%); }
    .sp-cover .inner { position:relative; padding:48px 0 44px; width:100%; }
    /* Text sits in a dark, slightly-blurred well so it stays legible over the
       bright sky in the launch photo, regardless of the crop. */
    .sp-cover-well { display:inline-block; max-width:700px; background:rgba(10,22,14,.74);
        border:1px solid rgba(255,255,255,.14); border-radius:14px; padding:26px 32px 28px;
        backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); box-shadow:0 14px 44px rgba(0,0,0,.38); }
    .sp-cover h1 { color:#fff; font-size:50px; font-weight:700; letter-spacing:-.01em; text-wrap:balance; margin-top:6px; text-shadow:0 2px 14px rgba(0,0,0,.35); }
    .sp-cover .sub { font-size:18px; color:#eaf4ee; margin-top:14px; max-width:600px; }
    .sp-cover .yr { margin-top:20px; font-size:13px; letter-spacing:.14em; text-transform:uppercase; color:#c6ecd6; font-weight:600; }

    /* Stats band */
    .sp-stats { background:var(--green); color:#fff; }
    .sp-stats .sp-statrow { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; padding:30px 0; align-items:start; }
    .sp-stat { text-align:center; display:flex; flex-direction:column; align-items:center; }
    .sp-stat + .sp-stat { border-left:1px solid rgba(255,255,255,.16); }
    .sp-stat .n { font-size:36px; font-weight:700; line-height:1; height:38px; display:flex; align-items:center; }
    .sp-stat .l { font-size:13px; color:#cdeedd; margin-top:10px; letter-spacing:.02em; white-space:nowrap; }

    .sp-section { padding:54px 0; }
    .sp-section.alt { background:var(--ground); }
    .sp-section h2 { font-size:30px; font-weight:700; text-wrap:balance; }
    .sp-section .lead { color:var(--muted); font-size:17px; max-width:760px; margin-top:14px; }
    .sp-hr { width:54px; height:4px; background:var(--bright); border-radius:3px; margin:0 0 18px; }

    /* Who / two-col */
    .sp-two { display:grid; grid-template-columns:1.15fr .85fr; gap:36px; align-items:center; }
    .sp-two img { width:100%; height:340px; object-fit:cover; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,.12); }
    .sp-two p { color:#3a403c; font-size:16px; }
    .sp-two p + p { margin-top:12px; }
    .sp-divisions { display:flex; flex-wrap:wrap; gap:8px; margin-top:18px; }
    .sp-divisions span { background:#eaf5ef; border:1px solid #d3e8dc; color:#166a3f; font-weight:600; font-size:13px; padding:6px 13px; border-radius:999px; }

    /* Showcase grid */
    .sp-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:26px; }
    .sp-card { border-radius:12px; overflow:hidden; background:#fff; border:1px solid var(--line); box-shadow:0 4px 16px rgba(0,0,0,.05); }
    .sp-card img { width:100%; height:170px; object-fit:cover; display:block; }
    .sp-card .b { padding:14px 16px 16px; }
    .sp-card .b h4 { font-size:16px; margin-bottom:5px; }
    .sp-card .b .tag { font-size:11px; letter-spacing:.06em; text-transform:uppercase; color:var(--bright); font-weight:700; }
    .sp-card .b p { color:var(--muted); font-size:13.5px; margin-top:6px; }

    /* Why sponsor */
    .sp-why { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-top:26px; }
    .sp-why .w { background:#fff; border:1px solid var(--line); border-radius:12px; padding:26px 24px; box-shadow:0 4px 16px rgba(0,0,0,.05); }
    .sp-why .w .ic { color:var(--bright); font-size:30px; line-height:1; }
    .sp-why .w h3 { font-size:19px; margin:14px 0 8px; }
    .sp-why .w p { color:var(--muted); font-size:14.5px; }

    /* Ways to support */
    .sp-ways { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; margin-top:24px; }
    .sp-way { display:flex; gap:16px; background:#fff; border:1px solid var(--line); border-radius:12px; padding:22px 24px; }
    .sp-way .n { font-size:26px; font-weight:700; color:var(--bright); line-height:1; flex:0 0 auto; }
    .sp-way h4 { font-size:17px; margin-bottom:6px; }
    .sp-way p { color:var(--muted); font-size:14px; }

    /* Tiers */
    .sp-tiers { margin-top:28px; overflow-x:auto; }
    .sp-tiers table { width:100%; border-collapse:collapse; min-width:720px; }
    .sp-tiers th,.sp-tiers td { text-align:center; padding:13px 12px; border-bottom:1px solid var(--line); font-size:13.5px; }
    .sp-tiers thead th { font-size:14px; font-weight:700; border-bottom:2px solid var(--green); }
    .sp-tiers thead th .amt { display:block; font-size:12px; font-weight:600; color:var(--muted); margin-top:3px; }
    .sp-tiers tbody th { text-align:left; font-weight:600; color:var(--ink); }
    .sp-tiers .chk { color:var(--green); font-weight:700; }
    .sp-tiers .dash { color:#c4ccc7; }
    .sp-tiers thead th.title { color:var(--gold); }
    .sp-tiers .note { font-size:12.5px; color:var(--muted); margin-top:12px; }

    /* Sponsors */
    .sp-logos { display:flex; flex-wrap:wrap; gap:16px; justify-content:center; margin-top:26px; }
    .sp-logo { flex:0 0 auto; width:186px; height:100px; background:#fff; border:1px solid var(--line); border-radius:10px; display:flex; align-items:center; justify-content:center; padding:16px 22px; box-shadow:0 3px 12px rgba(0,0,0,.05); }
    .sp-logo img { max-width:100%; max-height:100%; width:auto; height:auto; object-fit:contain; }

    /* CTA */
    .sp-cta { background:linear-gradient(135deg,#00612d,#00853e); color:#fff; border-radius:16px; padding:40px 40px; text-align:center; margin:44px 0; }
    .sp-cta h2 { font-size:28px; color:#fff; }
    .sp-cta p { color:#d9f0e2; margin:12px auto 22px; max-width:560px; }
    .sp-cta .btns { display:flex; gap:12px; justify-content:center; flex-wrap:wrap; }
    .sp-btn { display:inline-block; padding:13px 26px; border-radius:8px; font-weight:700; font-size:15px; text-decoration:none; }
    .sp-btn.solid { background:#fff; color:var(--green); }
    .sp-btn.ghost { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.5); }
    .sp-btn.pdf { background:var(--bright); color:#06301c; }

    .sp-toolbar { display:flex; justify-content:flex-end; gap:10px; padding:16px 0 0; }

    @media (max-width:820px){
        .sp-cover h1{font-size:34px;} .sp-two{grid-template-columns:1fr;} .sp-grid{grid-template-columns:1fr 1fr;}
        .sp-why{grid-template-columns:1fr;} .sp-ways{grid-template-columns:1fr;} .sp-stats .row{grid-template-columns:1fr 1fr;}
    }

    /* ---- PRINT: strip the site chrome, lay out clean PDF pages -------------- */
    @media print {
        @page { size:A4; margin:14mm 14mm; }
        html,body { background:#fff !important; }
        .page-head, .page-footer, .rd-navbar-wrap, header.page-head, footer,
        .sp-btn.pdf, .page-loader { display:none !important; }
        .sp-wrap { max-width:100%; padding:0; }
        .sp-cover { min-height:420px; break-after:page; }
        .sp-section, .sp-stats { break-inside:avoid; padding:22px 0; }
        .sp-cta { break-inside:avoid; }
        .sp-card, .sp-why .w, .sp-way { break-inside:avoid; }
        .sp, .sp * { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
        a[href]:after { content:""; }
    }
</style>

<main class="page-content sp">

    <!-- Cover -->
    <section class="sp-cover">
        <img class="bg" src="/images/content/aerospace/rocket-launch-still.jpg" alt="">
        <div class="veil"></div>
        <div class="inner"><div class="sp-wrap">
            <div class="sp-cover-well">
                <span class="kick">UNT Robotics &middot; Sponsorship</span>
                <h1>Fuel the next generation of engineers.</h1>
                <p class="sub">We design, build, test, and fly &mdash; rockets, robots, and everything in between. Your sponsorship puts your brand alongside the most ambitious student engineers at the University of North Texas.</p>
                <div class="yr">Sponsorship Flyer &middot; 2026&ndash;2027</div>
            </div>
        </div></div>
    </section>

    <!-- Stats -->
    <section class="sp-stats"><div class="sp-wrap"><div class="sp-statrow">
        <div class="sp-stat"><div class="n">400+</div><div class="l">Student members</div></div>
        <div class="sp-stat"><div class="n">6</div><div class="l">Divisions</div></div>
        <div class="sp-stat"><div class="n">1st</div><div class="l">IEEE Region 5 winners</div></div>
        <div class="sp-stat"><div class="n">7</div><div class="l">Botathon seasons</div></div>
    </div></div></section>

    <!-- Who we are -->
    <section class="sp-section"><div class="sp-wrap">
        <div class="sp-two">
            <div>
                <div class="sp-hr"></div>
                <h2>Who we are</h2>
                <p style="margin-top:16px;">UNT Robotics is an entirely student-led engineering organization at the University of North Texas &mdash; and one of the largest on campus. With roots going back decades and revived in 2018, we give students the chance to apply what they learn in class to exceptionally challenging, real-world projects.</p>
                <p>Through weekly workshops, hands-on projects, and national competitions, our members design and build robots and rockets from the ground up &mdash; hardware that costs thousands of dollars and pushes what students thought was possible. Because we run on a non-commercial model, <strong>we rely entirely on sponsors and donors to make it happen.</strong></p>
                <div class="sp-divisions">
                    <span>Aerospace</span><span>Recreational Robotics</span><span>Competitions</span>
                    <span>Fabrication &amp; 3D Printing</span><span>Operations</span><span>Outreach</span>
                </div>
            </div>
            <img src="/images/content/team/officers-2023.jpg" alt="UNT Robotics officers">
        </div>
    </div></section>

    <!-- What we build -->
    <section class="sp-section alt"><div class="sp-wrap">
        <div class="sp-hr"></div>
        <h2>What we build</h2>
        <p class="lead">From high-power rockets flown at NASA Student Launch to a self-driving sofa, our members take on projects across every discipline.</p>
        <div class="sp-grid">
            <div class="sp-card"><img src="/images/content/aerospace/nasa-sl-2022-launch.jpg" alt="High-power rocketry"><div class="b"><span class="tag">Aerospace</span><h4>High-Power Rocketry</h4><p>Custom airframes flown at NASA Student Launch, Marshall Space Flight Center.</p></div></div>
            <div class="sp-card"><img src="/images/content/ieee2019/build-5.jpg" alt="IEEE Region 5 competition robot"><div class="b"><span class="tag">Competition &mdash; 1st place</span><h4>IEEE Region 5</h4><p>Our autonomous competition robot, built to take on &mdash; and beat &mdash; eleven other universities.</p></div></div>
            <div class="sp-card"><img src="/images/content/rover/system-integration.jpg" alt="JPL rover"><div class="b"><span class="tag">Robotics</span><h4>JPL Open-Source Rover</h4><p>A six-wheel rocker-bogie rover running ROS 2 &mdash; our intro to real robotics.</p></div></div>
            <div class="sp-card"><img src="/images/content/scrappe/build-hdr.jpg" alt="Scrapp-E"><div class="b"><span class="tag">Flagship robot</span><h4>Scrapp-E</h4><p>A tracked companion robot with an animatronic head and gripper arms.</p></div></div>
            <div class="sp-card"><img src="/images/content/video/sofabot-poster.jpg" alt="Sofabot"><div class="b"><span class="tag">Recreational</span><h4>Sofabot</h4><p>A fully rideable, self-driving electric sofa &mdash; because we can.</p></div></div>
            <div class="sp-card"><img src="/images/content/botathon/s7-1.jpg" alt="Botathon"><div class="b"><span class="tag">Our own event</span><h4>Botathon</h4><p>Our annual robot-combat competition &mdash; seven seasons and counting.</p></div></div>
        </div>
    </div></section>

    <!-- Why sponsor -->
    <section class="sp-section"><div class="sp-wrap">
        <div class="sp-hr"></div>
        <h2>Why sponsor us?</h2>
        <p class="lead">Sponsoring UNT Robotics puts your brand in front of exceptional engineering talent &mdash; and helps build the next generation of your industry.</p>
        <div class="sp-why">
            <div class="w"><span class="ic thin-icon-pointer"></span><h3>Recruiting &amp; talent</h3><p>Gain face-to-face access to our engineering teams. The build-and-development experience tells you more about a candidate than a thousand interviews ever could.</p></div>
            <div class="w"><span class="ic thin-icon-lightbulb"></span><h3>Brand awareness</h3><p>Bring your brand into the UNT orbit. Our high-profile projects have gone viral on social media, featured on television, and drawn crowds at in-person events.</p></div>
            <div class="w"><span class="ic thin-icon-study"></span><h3>Community impact</h3><p>Your support funds STEM outreach with schools, scouts, and local organizations &mdash; inspiring the scientists and engineers of tomorrow.</p></div>
        </div>
    </div></section>

    <!-- Ways to support -->
    <section class="sp-section alt"><div class="sp-wrap">
        <div class="sp-hr"></div>
        <h2>Ways to support</h2>
        <p class="lead">Sponsorship is flexible &mdash; and we&rsquo;ll work with you to connect our members with your company&rsquo;s message.</p>
        <div class="sp-ways">
            <div class="sp-way"><div class="n">1</div><div><h4>Financially</h4><p>Fund the parts, equipment, and materials that go into our competition robots and rockets &mdash; the fastest way to make a direct impact.</p></div></div>
            <div class="sp-way"><div class="n">2</div><div><h4>Give a talk</h4><p>Come to campus and inspire young talent with a talk about your company and your industry.</p></div></div>
            <div class="sp-way"><div class="n">3</div><div><h4>Mentorship</h4><p>Industry experts and UNT alumni are invited to get involved as mentors and contributors on our advanced projects.</p></div></div>
            <div class="sp-way"><div class="n">4</div><div><h4>Volunteer &amp; outreach</h4><p>Join us at outreach events and bring hands-on rocketry and robotics to the next generation of engineers.</p></div></div>
        </div>
    </div></section>

    <!-- Tiers -->
    <section class="sp-section"><div class="sp-wrap">
        <div class="sp-hr"></div>
        <h2>Sponsorship tiers</h2>
        <p class="lead">Every level makes a real difference. Prefer to give in kind (parts, equipment, services)? We value in-kind support at retail toward these tiers.</p>
        <div class="sp-tiers">
            <table>
                <thead><tr>
                    <th style="text-align:left;">Benefit</th>
                    <th>Friend<span class="amt">$250+</span></th>
                    <th>Bronze<span class="amt">$1,000</span></th>
                    <th>Silver<span class="amt">$2,000</span></th>
                    <th>Gold<span class="amt">$3,000</span></th>
                    <th class="title">Title<span class="amt">$5,000</span></th>
                </tr></thead>
                <tbody>
                    <tr><th>Social-media shout-out (@untrobotics)</th><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Name &amp; logo on our website</th><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Sponsor goody bag (apparel, stickers, team photo)</th><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Logo on event banners</th><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Logo on team shirts &amp; transport vehicles</th><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Logo on competition robots &amp; rockets</th><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Logo / name in our project &amp; flight videos</th><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Campus talk &amp; resume-database access</th><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Premier placement &amp; project naming</th><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="chk">&check;</td><td class="chk">&check;</td></tr>
                    <tr><th>Vehicle / experiment naming rights + first refusal next year</th><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="dash">&ndash;</td><td class="chk">&check;</td></tr>
                </tbody>
            </table>
            <p class="note"><strong>All donations are tax-deductible</strong> &mdash; made through the UNT College of Engineering. Tiers are a guide; we&rsquo;re happy to build a custom package (including in-kind support, valued at retail) around what matters to your company. Amounts shown are annual.</p>
        </div>
    </div></section>

    <!-- Current sponsors -->
    <section class="sp-section alt"><div class="sp-wrap" style="text-align:center;">
        <div class="sp-hr" style="margin:0 auto 18px;"></div>
        <h2>Our sponsors</h2>
        <p class="lead" style="margin:14px auto 0;">We&rsquo;re proud to be supported by companies and organizations who believe in student engineering.</p>
        <div class="sp-logos">
            <div class="sp-logo"><img src="/images/sponsor-logos/respec.jpg" alt="RESPEC"></div>
            <div class="sp-logo"><img src="/images/sponsor-logos/servocity.jpg" alt="ServoCity"></div>
            <div class="sp-logo"><img src="/images/sponsor-logos/ieee-ft-worth.jpg" alt="IEEE Fort Worth"></div>
            <div class="sp-logo"><img src="/images/sponsor-logos/eagles-nest.jpg" alt="Eagle's Nest"></div>
            <div class="sp-logo"><img src="/images/sponsor-logos/studyology.jpg" alt="Studyology"></div>
        </div>
    </div></section>

    <!-- CTA -->
    <div class="sp-wrap"><div class="sp-cta">
        <h2>Let&rsquo;s build something together.</h2>
        <p>Whether it&rsquo;s a one-off donation, a multi-year partnership, or in-kind support, we&rsquo;ll be flexible and make sure your investment counts. Get in touch to discuss sponsoring UNT Robotics.</p>
        <div class="btns">
            <a class="sp-btn solid" href="mailto:hello@untrobotics.com?subject=Sponsoring%20UNT%20Robotics">hello@untrobotics.com</a>
            <a class="sp-btn ghost" href="/sponsorships#donation-widget">Donate online</a>
            <a class="sp-btn ghost" href="tel:+19403040795">(940) 304-0795</a>
        </div>
        <div class="btns" style="margin-top:14px;">
            <a class="sp-btn pdf" href="/downloads/unt-robotics-sponsorship.pdf" target="_blank" rel="noopener">&#8681; Download this flyer (PDF)</a>
        </div>
    </div></div>

</main>
<?php
footer();
