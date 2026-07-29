<?php
require('template/top.php');
head('Projects', true);

// Projects showcase. Content sourced from the club's own archives; photos live
// under /images/content/. Each card: hero image, title, blurb, status tag.
$projects = array(
    array(
        'title' => 'High-Power Rocketry',
        'tag' => 'Aerospace Division',
        'img' => 'aerospace/hpr-launch-prep.jpg',
        'body' => "Our Aerospace Division designs, builds, and flies high-power rockets from the ground up &mdash; custom fiberglass airframes, hand-sewn parachutes, 3D-printed fin cans, and full paint jobs. We competed in <strong>NASA Student Launch</strong> for two seasons, culminating in a launch at NASA&rsquo;s Marshall Space Flight Center in Huntsville, Alabama. Members earn their Level 1 and Level 2 certifications and fly regularly with the North Texas rocketry community.",
        'gallery' => array('aerospace/hpr-custom-paint.jpg', 'aerospace/rocket-launch-still.jpg', 'aerospace/hpr-parachute-sewing.jpg'),
    ),
    array(
        'title' => 'Scrapp-E',
        'tag' => 'Flagship Robot',
        'img' => 'scrappe/build-hdr.jpg',
        'body' => "Scrapp-E is our mascot brought to life: a tracked companion robot with an animatronic head, mechanical iris eyes, and modular gripper arms. Chosen by a club-wide vote, the team has built a 3D-printed gearbox, a working animatronic hand driven by an Arduino and servos, and an aluminum drive frame. It&rsquo;s equal parts mechanical, electrical, and software &mdash; a project anyone can plug into.",
        'gallery' => array('scrappe/first-chassis-mount.jpg', 'scrappe/build-progress.jpg'),
    ),
    array(
        'title' => 'JPL Open-Source Rover',
        'tag' => 'Robotics',
        'img' => 'rover/system-integration.jpg',
        'body' => "A six-wheel rocker-bogie rover built from NASA JPL&rsquo;s open-source design, running <strong>ROS 2</strong> on a Raspberry Pi with RoboClaw motor controllers. Members split into chassis, computer-vision, sensors, and movement teams to bring it together &mdash; and even visited the real rover at JPL. It&rsquo;s our hands-on introduction to real robotics software and systems integration.",
        'gallery' => array('rover/six-motors.jpg', 'rover/workshop-wide.jpg', 'rover/raspberry-pi-wiring.jpg'),
    ),
    array(
        'title' => 'Sofabot',
        'tag' => 'Recreational Build',
        'img' => 'sofabot/build-1.jpg',
        'body' => "Exactly what it sounds like: a fully rideable, self-driving <strong>electric sofa</strong>. Two batteries, a geared drivetrain, and a Raspberry Pi with a high-torque steering servo push it to around 12 mph. Built over years in members&rsquo; garages, Sofabot is the club&rsquo;s favorite &ldquo;because we can&rdquo; project &mdash; and yes, there&rsquo;s video of its very first drive.",
        'gallery' => array('sofabot/circle-done.jpg', 'sofabot/early-build.jpg'),
        'video' => 'video/sofabot-first-drive.mov',
    ),
    array(
        'title' => 'Botathon',
        'tag' => 'Annual Competition',
        'img' => 'botathon/s7-1.jpg',
        'body' => "Our flagship annual event: an approachable, cross-disciplinary robot-combat competition open to every major and skill level. Teams build Arduino/ESP32 RC battle bots &mdash; with live 3D printing on hand &mdash; and face off in a single-elimination bracket at Discovery Park. Every year gets a fresh theme, from the very first &ldquo;Balloons&rdquo; season in 2019 to today. <a href=\"/botathon\">Learn more &rarr;</a>",
        'gallery' => array('botathon/s7-2.jpg', 'botathon/s3-1.jpg', 'botathon/s3-2.jpg'),
    ),
    array(
        'title' => '3D Printing Lab',
        'tag' => 'Fabrication',
        'img' => 'printing/nosecone-ocean-pattern.jpg',
        'body' => "A growing fleet of printers &mdash; Creality Ender 3, Bambu A1, the large-format Modix Big 120X, and a Stratasys uPrint &mdash; backs every other project: rocket nose cones, fin cans, custom ejection baffles, robot gearboxes, and more. We run workshops on CAD and printing, and are spinning up a filament-recycling effort to keep it sustainable.",
        'gallery' => array('printing/finished-print.jpg', 'printing/paint-booth.jpg'),
    ),
    array(
        'title' => 'IEEE Region 5',
        'tag' => 'Competition',
        'img' => 'aerospace/hpr-third-scale-model.jpg',
        'body' => "Our competition team represents UNT at the IEEE Region 5 student competitions &mdash; including a <strong>first-place autonomous drone</strong> that identified and popped balloons by color using computer vision. The team continues to build for IEEE&rsquo;s autonomous robotics challenges, putting our controls, vision, and mechanical work to the test against schools across the region.",
        'gallery' => array(),
    ),
    array(
        'title' => 'Recreational Builds',
        'tag' => 'For Everyone',
        'img' => 'events/group-work-session.jpg',
        'body' => "Not every project is a competition. Our rec builds are for anyone who just wants to make something cool with a team &mdash; a from-scratch Arduino <strong>claw machine</strong>, a refurbished arcade-vending machine (&ldquo;Vendarcading&rdquo;), and whatever the membership dreams up next. Pitch an idea, rally a team, and we&rsquo;ll help you build it.",
        'gallery' => array(),
    ),
);
?>
<style>
    .proj-hero { padding: 60px 0 20px; text-align: center; }
    .proj-hero h1 { margin-bottom: 10px; }
    .proj-hero p { max-width: 720px; margin: 0 auto; color: #555; font-size: 17px; line-height: 1.6; }
    .proj-list { max-width: 1080px; margin: 0 auto; padding: 20px 15px 60px; }
    .proj { display: flex; gap: 34px; align-items: flex-start; margin-bottom: 64px; }
    .proj:nth-child(even) { flex-direction: row-reverse; }
    .proj-media { flex: 0 0 46%; max-width: 46%; }
    .proj-media .main { width: 100%; border-radius: 10px; display: block; box-shadow: 0 6px 24px rgba(0,0,0,.12); }
    .proj-thumbs { display: flex; gap: 8px; margin-top: 8px; }
    .proj-thumbs img { width: 100%; height: 84px; object-fit: cover; border-radius: 6px; }
    .proj-body { flex: 1 1 auto; }
    .proj-tag { display: inline-block; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: #24c57c; font-weight: 700; margin-bottom: 6px; }
    .proj-body h3 { margin: 0 0 12px; font-size: 26px; }
    .proj-body p { color: #444; line-height: 1.7; font-size: 16px; }
    .proj-body a { color: #24c57c; font-weight: 600; }
    .proj-video { margin-top: 8px; width: 100%; border-radius: 8px; display: block; }
    @media (max-width: 767px) {
        .proj, .proj:nth-child(even) { flex-direction: column; gap: 16px; }
        .proj-media, .proj:nth-child(even) .proj-media { max-width: 100%; flex-basis: auto; }
    }
</style>
<main class="page-content">
    <section class="proj-hero">
        <div class="shell">
            <h1>What We Build</h1>
            <p>UNT Robotics is a &ldquo;design, build, test, and fly&rdquo; organization. From high-power rockets and autonomous rovers to a self-driving sofa, here&rsquo;s some of what our members have made &mdash; across every discipline and skill level.</p>
        </div>
    </section>
    <div class="proj-list">
        <?php foreach ($projects as $p): ?>
            <div class="proj">
                <div class="proj-media">
                    <img class="main" src="/images/content/<?php echo htmlspecialchars($p['img']); ?>" alt="<?php echo htmlspecialchars($p['title']); ?>" loading="lazy">
                    <?php if (!empty($p['video'])): ?>
                        <video class="proj-video" controls preload="none" poster="/images/content/<?php echo htmlspecialchars($p['img']); ?>">
                            <source src="/images/content/<?php echo htmlspecialchars($p['video']); ?>" type="video/quicktime">
                        </video>
                    <?php endif; ?>
                    <?php if (!empty($p['gallery'])): ?>
                        <div class="proj-thumbs">
                            <?php foreach ($p['gallery'] as $g): ?>
                                <img src="/images/content/<?php echo htmlspecialchars($g); ?>" alt="" loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="proj-body">
                    <span class="proj-tag"><?php echo htmlspecialchars($p['tag']); ?></span>
                    <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                    <p><?php echo $p['body']; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
        <div style="text-align:center;">
            <a href="/join/discord" class="btn btn-primary">Join us and build something</a>
        </div>
    </div>
</main>
<?php
footer();
