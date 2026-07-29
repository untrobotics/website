<?php
require('template/top.php');
head('Competitions', true);
?>
<style>
    .comp-hero { padding: 60px 0 10px; text-align: center; }
    .comp-hero p { max-width: 720px; margin: 12px auto 0; color: #555; font-size: 17px; line-height: 1.6; }
    .comp-wrap { max-width: 1000px; margin: 0 auto; padding: 20px 15px 60px; }
    .comp { display: flex; gap: 30px; align-items: center; margin-bottom: 56px; }
    .comp:nth-child(even) { flex-direction: row-reverse; }
    .comp-img { flex: 0 0 44%; max-width: 44%; }
    .comp-img img { width: 100%; height: 280px; object-fit: cover; border-radius: 10px; box-shadow: 0 6px 24px rgba(0,0,0,.12); }
    .comp-text { flex: 1 1 auto; }
    .comp-text .tag { display: inline-block; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: #24c57c; font-weight: 700; margin-bottom: 6px; }
    .comp-text h3 { margin: 0 0 12px; font-size: 25px; }
    .comp-text p { color: #444; line-height: 1.7; font-size: 16px; }
    .comp-text a { color: #24c57c; font-weight: 600; }
    @media (max-width: 767px) { .comp, .comp:nth-child(even) { flex-direction: column; gap: 14px; } .comp-img, .comp:nth-child(even) .comp-img { max-width: 100%; flex-basis: auto; } }
</style>
<main class="page-content">
    <section class="comp-hero">
        <div class="shell">
            <h1>Competitions</h1>
            <p>We put our skills to the test against schools across the country &mdash; and host our own. From autonomous drones and high-power rockets to a beginner-friendly battle-bot bracket, here&rsquo;s where UNT Robotics competes.</p>
        </div>
    </section>
    <div class="comp-wrap">

        <div class="comp">
            <div class="comp-img"><img src="/images/content/ieee2019/build-1.jpg" alt="IEEE Region 5 robotics build" loading="lazy"></div>
            <div class="comp-text">
                <span class="tag">IEEE Region 5 &mdash; 1st Place</span>
                <h3>IEEE Region 5 Student Robotics Competition</h3>
                <p>Our IEEE Robotics &amp; Automation Society team took <strong>first place</strong> at the IEEE Region 5 Student Robotics Competition, beating out eleven other universities. The challenge: program a drone to autonomously identify balloons by color using computer vision, fly toward them, and pop them in a judge-determined order &mdash; entirely pre-programmed, with no human input once it launched. The team continues to build for IEEE&rsquo;s autonomous robotics challenges each year.</p>
            </div>
        </div>

        <div class="comp">
            <div class="comp-img"><img src="/images/content/aerospace/nasa-sl-2023-1.jpg" alt="NASA Student Launch rocket" loading="lazy"></div>
            <div class="comp-text">
                <span class="tag">NASA Student Launch</span>
                <h3>NASA Student Launch (USLI)</h3>
                <p>Our High-Power Rocketry team competed in NASA&rsquo;s Student Launch challenge for two seasons &mdash; designing, building, and flying high-power rockets with custom science payloads through NASA&rsquo;s full review process (Proposal, PDR, CDR, FRR). The 2022&ndash;23 rocket, &ldquo;Phoenix,&rdquo; flew in person at <strong>NASA&rsquo;s Marshall Space Flight Center</strong> in Huntsville, Alabama. <a href="/projects">See the rocketry project &rarr;</a></p>
            </div>
        </div>

        <div class="comp">
            <div class="comp-img"><img src="/images/content/botathon/s7-1.jpg" alt="Botathon competition" loading="lazy"></div>
            <div class="comp-text">
                <span class="tag">Our Own Event</span>
                <h3>Botathon</h3>
                <p>Botathon is the competition we host ourselves: an approachable, cross-disciplinary robot-combat event open to every major and skill level. Teams build Arduino/ESP32 RC battle bots and face off in a single-elimination bracket at Discovery Park, with a fresh theme every year. It&rsquo;s many members&rsquo; first taste of competitive robotics. <a href="/botathon">Learn about Botathon &rarr;</a></p>
            </div>
        </div>

        <div class="comp">
            <div class="comp-img"><img src="/images/content/rover/system-integration.jpg" alt="Autonomous rover build" loading="lazy"></div>
            <div class="comp-text">
                <span class="tag">IEEE Region 5</span>
                <h3>Autonomous Rover</h3>
                <p>Our collegiate team also competes in the IEEE Region 5 Autonomous Rover competition, where our members have been commended for their mechanical-engineering work. It&rsquo;s a hands-on proving ground for the controls, sensing, and software skills we build through the year.</p>
            </div>
        </div>

        <div style="text-align:center;">
            <a href="/join/discord" class="btn btn-primary">Compete with us</a>
        </div>
    </div>
</main>
<?php
footer();
