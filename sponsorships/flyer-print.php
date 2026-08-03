<?php
/* Standalone print brochure — source for the sponsorship PDF. Rendered to
   downloads/unt-robotics-sponsorship.pdf via headless Chrome (print media).
   Designed like the old aerospace deck: green/white with flowing line-art. */
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>UNT Robotics — Sponsorship</title>
<style>
    :root{ --green:#00853e; --green-d:#016b33; --ink:#1c211e; --muted:#5a615c; --line:#e4eae6; }
    *{ box-sizing:border-box; margin:0; padding:0; }
    html,body{ font-family:'Poppins','Helvetica Neue',Arial,sans-serif; color:var(--ink); -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .page{ position:relative; width:210mm; min-height:297mm; padding:20mm 18mm; overflow:hidden; page-break-after:always; }
    .page:last-child{ page-break-after:auto; }
    .page.green{ background:var(--green); color:#fff; }

    h1,h2,h3{ line-height:1.1; }
    .eyebrow{ font-size:11px; letter-spacing:.22em; text-transform:uppercase; font-weight:700; }
    p{ line-height:1.62; font-size:12.5px; }
    .green h2{ color:#fff; }
    h2.sec{ color:var(--green); font-size:26px; font-weight:700; margin-bottom:4px; }
    h2.sec + .rule{ width:46px; height:4px; background:var(--green); border-radius:3px; margin:8px 0 18px; }
    h3.sub{ color:var(--green-d); font-size:14px; font-weight:700; margin:16px 0 4px; }
    .lead{ color:var(--muted); font-size:13.5px; max-width:150mm; }
    .footic{ position:absolute; left:18mm; bottom:12mm; font-size:9px; letter-spacing:.14em; text-transform:uppercase; color:var(--muted); }
    .green .footic{ color:rgba(255,255,255,.6); }

    /* line-art helpers */
    .art{ position:absolute; pointer-events:none; }
    svg .stroke{ fill:none; stroke-linecap:round; stroke-linejoin:round; }

    /* ---------- COVER ---------- */
    .cover-logo{ width:104px; height:auto; margin-bottom:14px; display:block; }
    .cover-title{ font-size:62px; font-weight:700; letter-spacing:-.02em; }
    .cover-sub{ font-size:20px; font-weight:500; margin-top:2px; color:#eaf7ef; }
    .cover-year{ font-size:12px; letter-spacing:.2em; text-transform:uppercase; margin-top:8px; color:#bfe6cf; }
    .cover-tag{ position:absolute; left:18mm; bottom:16mm; font-style:italic; font-size:15px; color:#eaf7ef; }
    .cover-coe{ position:absolute; right:18mm; bottom:15mm; text-align:right; }
    .cover-coe .u{ font-size:26px; font-weight:800; letter-spacing:.02em; }
    .cover-coe .c{ font-size:9px; letter-spacing:.18em; text-transform:uppercase; opacity:.85; }

    /* stat chips */
    .stats{ display:flex; gap:0; margin:22px 0 6px; border:1px solid var(--line); border-radius:10px; overflow:hidden; }
    .stats .s{ flex:1; text-align:center; padding:14px 8px; }
    .stats .s + .s{ border-left:1px solid var(--line); }
    .stats .n{ font-size:26px; font-weight:700; color:var(--green); line-height:1; }
    .stats .l{ font-size:10.5px; color:var(--muted); margin-top:6px; }

    .divisions{ display:flex; flex-wrap:wrap; gap:6px; margin-top:14px; }
    .divisions span{ background:#eef7f1; border:1px solid #d5e8dd; color:var(--green-d); font-weight:600; font-size:10.5px; padding:4px 10px; border-radius:999px; }

    /* build grid */
    .builds{ display:grid; grid-template-columns:1fr 1fr; gap:9px; margin-top:6px; }
    .build{ border:1px solid var(--line); border-radius:9px; overflow:hidden; }
    .build img{ width:100%; height:150px; object-fit:cover; display:block; }
    .build .cap{ padding:7px 10px 9px; }
    .build .t{ font-size:11px; letter-spacing:.05em; text-transform:uppercase; color:var(--green); font-weight:700; }
    .build .n{ font-size:13px; font-weight:600; margin-top:1px; }

    /* why cards */
    .why{ display:grid; grid-template-columns:1fr 1fr; gap:14px 22px; margin-top:6px; }
    .why h3{ color:var(--green-d); font-size:13.5px; font-weight:700; display:flex; align-items:center; gap:8px; }
    .why p{ font-size:12px; color:#3c433e; margin-top:3px; }
    .why svg{ flex:0 0 auto; }

    /* tier table */
    table.tiers{ width:100%; border-collapse:collapse; margin-top:4px; }
    table.tiers th, table.tiers td{ padding:8.5px 6px; text-align:center; font-size:11px; border-bottom:1px solid var(--line); }
    table.tiers thead th{ font-weight:700; border-bottom:2px solid var(--green); }
    table.tiers thead .amt{ display:block; font-size:10px; color:var(--muted); font-weight:600; }
    table.tiers thead .title{ color:#b8912e; }
    table.tiers tbody th{ text-align:left; font-weight:500; color:var(--ink); font-size:11px; }
    .dot{ display:inline-block; width:11px; height:11px; border-radius:50%; }
    .dot.on{ background:var(--green); }
    .dot.off{ background:#dfe6e1; }
    .taxnote{ margin-top:12px; font-size:11px; color:var(--muted); }
    .taxnote strong{ color:var(--green-d); }

    .ways{ display:grid; grid-template-columns:1fr 1fr; gap:8px 18px; margin-top:8px; }
    .ways .w{ font-size:11.5px; } .ways .w b{ color:var(--green-d); }

    /* back cover */
    .back-cta{ font-style:italic; font-weight:700; font-size:40px; line-height:1.12; margin-top:auto; }
    .back-contact{ margin-top:22px; font-size:14px; } .back-contact a{ color:#fff; }
</style>
</head>
<body>

<!-- ============ PAGE 1 — COVER ============ -->
<section class="page green">
    <!-- decorative stars -->
    <svg class="art" style="top:0;left:0;width:210mm;height:297mm;opacity:.9" viewBox="0 0 595 842" aria-hidden="true">
        <g stroke="#ffffff" stroke-width="1.4" class="stroke" opacity=".55">
            <path d="M70 300 l0 14 M63 307 l14 0"/><path d="M120 250 l0 10 M115 255 l10 0"/>
            <path d="M500 120 l0 12 M494 126 l12 0"/><path d="M470 210 l0 9 M465.5 214.5 l9 0"/>
            <path d="M520 300 l0 10 M515 305 l10 0"/><path d="M90 470 l0 10 M85 475 l10 0"/>
        </g>
        <g fill="#ffffff" opacity=".5"><circle cx="150" cy="140" r="2.2"/><circle cx="450" cy="330" r="2.2"/><circle cx="510" cy="470" r="2.2"/><circle cx="60" cy="520" r="2.2"/><circle cx="300" cy="90" r="2"/></g>
    </svg>
    <!-- rocket line-art, lower-right -->
    <img class="art" src="/images/content/lineart/rocket-white.svg" style="right:18mm;bottom:32mm;width:190px;opacity:.92" alt="">

    <img class="cover-logo" src="/images/unt-robotics-brand-logo-white.svg" alt="UNT Robotics">
    <div class="cover-title">UNT Robotics</div>
    <div class="cover-sub">Sponsorship</div>
    <div class="cover-year">2026 &ndash; 2027</div>

    <div class="cover-tag">Changing the world, one student at a time</div>
    <div class="cover-coe"><div class="u">UNT</div><div class="c">College of Engineering</div></div>
</section>

<!-- ============ PAGE 2 — WHO WE ARE ============ -->
<section class="page">
    <img class="art" src="/images/content/lineart/cog-green.svg" style="right:12mm;bottom:34mm;width:200px;opacity:.12" alt="">
    <div class="eyebrow" style="color:var(--green)">UNT Robotics</div>
    <h2 class="sec" style="margin-top:6px">Who we are</h2><div class="rule"></div>
    <p style="max-width:150mm">UNT Robotics is an entirely student-led engineering organization at the University of North Texas &mdash; and one of the largest on campus. With roots going back decades and revived in 2018, we give students the chance to apply what they learn in class to exceptionally challenging, real-world projects.</p>
    <p style="max-width:150mm;margin-top:10px">We are not a social club. Through weekly workshops, hands-on projects, and national competitions, our members design and build robots and rockets from the ground up &mdash; hardware that costs thousands of dollars and pushes what students thought was possible. Because we run on a non-commercial model, <strong>we rely entirely on sponsors and donors to make it happen.</strong></p>

    <div class="stats">
        <div class="s"><div class="n">400+</div><div class="l">Student members</div></div>
        <div class="s"><div class="n">6</div><div class="l">Divisions</div></div>
        <div class="s"><div class="n">1st</div><div class="l">IEEE Region 5 winners</div></div>
        <div class="s"><div class="n">7</div><div class="l">Botathon seasons</div></div>
    </div>

    <h3 class="sub">Our divisions</h3>
    <div class="divisions"><span>Aerospace</span><span>Recreational Robotics</span><span>Competitions</span><span>Fabrication &amp; 3D Printing</span><span>Operations</span><span>Outreach</span></div>
    <div class="footic">UNT Robotics &middot; Sponsorship 2026&ndash;2027</div>
</section>

<!-- ============ PAGE 3 — WHAT WE BUILD ============ -->
<section class="page">
    <img class="art" src="/images/content/lineart/rocket-green.svg" style="right:16mm;top:16mm;width:60px;opacity:.16" alt="">
    <h2 class="sec">What we build</h2><div class="rule"></div>
    <p class="lead">From high-power rockets flown at NASA Student Launch to a self-driving sofa, our members take on ambitious projects across every discipline &mdash; and win.</p>
    <div class="builds" style="margin-top:14px">
        <div class="build"><img src="/images/content/aerospace/nasa-sl-2022-launch.jpg"><div class="cap"><div class="t">Aerospace</div><div class="n">High-Power Rocketry</div></div></div>
        <div class="build"><img src="/images/content/ieee2019/build-5.jpg"><div class="cap"><div class="t">Competition &mdash; 1st place</div><div class="n">IEEE Region 5</div></div></div>
        <div class="build"><img src="/images/content/rover/system-integration.jpg"><div class="cap"><div class="t">Robotics</div><div class="n">JPL Open-Source Rover</div></div></div>
        <div class="build"><img src="/images/content/scrappe/build-hdr.jpg"><div class="cap"><div class="t">Flagship robot</div><div class="n">Scrapp-E</div></div></div>
        <div class="build"><img src="/images/content/video/sofabot-poster.jpg"><div class="cap"><div class="t">Recreational</div><div class="n">Sofabot</div></div></div>
        <div class="build"><img src="/images/content/botathon/s7-1.jpg"><div class="cap"><div class="t">Our own event</div><div class="n">Botathon</div></div></div>
    </div>
    <div class="footic">UNT Robotics &middot; Sponsorship 2026&ndash;2027</div>
</section>

<!-- ============ PAGE 4 — WHY SPONSOR ============ -->
<section class="page">
    <!-- circuit line-art, bottom-right -->
    <img class="art" src="/images/content/lineart/circuit-board-green.svg" style="right:14mm;bottom:26mm;width:220px;opacity:.11" alt="">
    <h2 class="sec">Why sponsor us?</h2><div class="rule"></div>
    <p class="lead">Sponsoring UNT Robotics puts your brand in front of exceptional engineering talent &mdash; and helps build the next generation of your industry.</p>
    <div class="why" style="margin-top:16px">
        <div><h3>&#9679;&nbsp; Recruiting &amp; talent</h3><p>Gain face-to-face access to our engineering teams. The build-and-development experience tells you more about a candidate than a thousand interviews could.</p></div>
        <div><h3>&#9679;&nbsp; Brand awareness</h3><p>Bring your brand into the UNT orbit. Our high-profile projects have gone viral on social media, featured on television, and drawn crowds at in-person events.</p></div>
        <div><h3>&#9679;&nbsp; Community impact</h3><p>Your support funds STEM outreach with schools, scouts, and local organizations &mdash; inspiring the scientists and engineers of tomorrow.</p></div>
        <div><h3>&#9679;&nbsp; Innovation</h3><p>Our competitions are a breeding ground for new technology, developed and tested from scratch by students solving real, hard problems.</p></div>
    </div>

    <h3 class="sub" style="margin-top:22px">Ways to support</h3>
    <div class="ways">
        <div class="w"><b>Financially</b> &mdash; fund the parts &amp; equipment behind our robots and rockets.</div>
        <div class="w"><b>Give a talk</b> &mdash; come to campus and inspire young talent.</div>
        <div class="w"><b>Mentorship</b> &mdash; industry experts &amp; alumni guide our advanced projects.</div>
        <div class="w"><b>Volunteer &amp; outreach</b> &mdash; bring hands-on STEM to the next generation.</div>
    </div>
    <div class="footic">UNT Robotics &middot; Sponsorship 2026&ndash;2027</div>
</section>

<!-- ============ PAGE 5 — TIERS ============ -->
<section class="page">
    <h2 class="sec">Sponsorship tiers</h2><div class="rule"></div>
    <p class="lead">Every level makes a real difference. Prefer to give in kind (parts, equipment, services)? We value in-kind support at retail toward these tiers.</p>
    <table class="tiers" style="margin-top:14px">
        <thead><tr>
            <th style="text-align:left">Benefit</th>
            <th>Friend<span class="amt">$250+</span></th>
            <th>Bronze<span class="amt">$1,000</span></th>
            <th>Silver<span class="amt">$2,000</span></th>
            <th>Gold<span class="amt">$3,000</span></th>
            <th class="title">Title<span class="amt">$5,000</span></th>
        </tr></thead>
        <tbody>
            <tr><th>Social-media shout-out (@untrobotics)</th><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Name &amp; logo on our website</th><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Sponsor goody bag (apparel, stickers, team photo)</th><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Logo on event banners</th><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Logo on team shirts &amp; transport vehicles</th><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Logo on competition robots &amp; rockets</th><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Logo / name in our project &amp; flight videos</th><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Campus talk &amp; resume-database access</th><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Premier placement &amp; project naming</th><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot on"></span></td><td><span class="dot on"></span></td></tr>
            <tr><th>Vehicle / experiment naming rights + first refusal</th><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot off"></span></td><td><span class="dot on"></span></td></tr>
        </tbody>
    </table>
    <p class="taxnote"><strong>All donations are tax-deductible</strong> &mdash; made through the UNT College of Engineering. Tiers are a guide; we&rsquo;re happy to build a custom package around what matters to your company. Amounts shown are annual; the Title tier includes first refusal for the following year.</p>
    <!-- rocket motif, lower-right -->
    <img class="art" src="/images/content/lineart/rocket-green.svg" style="right:14mm;bottom:22mm;width:150px;opacity:.12" alt="">
    <div class="footic">UNT Robotics &middot; Sponsorship 2026&ndash;2027</div>
</section>

<!-- ============ PAGE 6 — BACK COVER ============ -->
<section class="page green" style="display:flex;flex-direction:column">
    <img class="art" src="/images/content/lineart/orbit-white.svg" style="right:14mm;top:16mm;width:170px;opacity:.5" alt="">
    <img class="cover-logo" src="/images/unt-robotics-brand-logo-white.svg" alt="UNT Robotics">
    <div style="font-size:20px;font-weight:700;margin-top:8px">UNT Robotics</div>
    <div class="eyebrow" style="color:#bfe6cf;margin-top:4px">Design. Build. Test. Fly.</div>

    <div class="back-cta">With your support,<br>the sky is <span style="text-decoration:underline;text-decoration-thickness:2px;text-underline-offset:4px">not</span> the limit.</div>

    <div class="back-contact">
        Get in touch to discuss sponsoring UNT Robotics:<br>
        <strong>hello@untrobotics.com</strong> &nbsp;&middot;&nbsp; (940)&nbsp;304-0795 &nbsp;&middot;&nbsp; untrobotics.com
    </div>
    <div style="margin-top:26px;font-size:11px;opacity:.8">3940 N Elm St, Denton, TX 76207 &nbsp;&middot;&nbsp; @untrobotics</div>
</section>

</body>
</html>
