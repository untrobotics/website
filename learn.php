<?php
require('template/top.php');
head('Learn', true);

// Getting-started guides: each topic points to genuinely good, free, beginner
// tutorials students can follow at home. Links verified before publishing.
$guides = array(
    array(
        'title' => 'Arduino',
        'icon' => 'thin-icon-pointer',
        'body' => "The Arduino Uno is the best place to start &mdash; cheap, beginner-friendly, and the controller behind most of our Botathon bots. Install the free Arduino IDE, run the <em>Blink</em> example, then work up to reading sensors and driving outputs.",
        'links' => array(
            array('Official &ldquo;Getting Started with Arduino&rdquo;', 'https://docs.arduino.cc/learn/starting-guide/getting-started-arduino/'),
            array('Paul McWhorter&rsquo;s Arduino video course (YouTube)', 'https://www.youtube.com/playlist?list=PLGs0VKk2DiYw-L-RibttcvK-WBZm8WLEP'),
        ),
    ),
    array(
        'title' => 'Raspberry Pi',
        'icon' => 'thin-icon-study',
        'body' => "When a build needs a real computer &mdash; cameras, computer vision, ROS &mdash; we use a Raspberry Pi. Flash the OS with the official Imager, boot into Linux, and start driving its GPIO pins with Python.",
        'links' => array(
            array('Official &ldquo;Getting started with Raspberry Pi&rdquo; pathway', 'https://projects.raspberrypi.org/en/pathways/getting-started-with-raspberry-pi'),
            array('More beginner projects (projects.raspberrypi.org)', 'https://projects.raspberrypi.org/en/projects'),
        ),
    ),
    array(
        'title' => 'Motors &amp; the L298N Driver',
        'icon' => 'thin-icon-car',
        'body' => "A microcontroller can&rsquo;t power a motor directly. Our standard setup is a basic 6V DC motor driven by an <strong>L298N</strong> H-bridge: direction pins pick which way it spins, a PWM pin sets the speed, and one L298N runs two motors &mdash; enough for a two-wheel robot.",
        'links' => array(
            array('L298N + Arduino, step by step (Last Minute Engineers)', 'https://lastminuteengineers.com/l298n-dc-stepper-driver-arduino-tutorial/'),
            array('DroneBot Workshop: controlling DC motors', 'https://dronebotworkshop.com/dc-motors-l298n-h-bridge/'),
        ),
    ),
    array(
        'title' => 'Servos',
        'icon' => 'thin-icon-lightbulb',
        'body' => "Servos hold a precise angle &mdash; perfect for steering, arms, and grippers. Three wires, one line of code (<code>servo.write(90)</code>). We use them everywhere, from Sofabot&rsquo;s steering to Scrapp-E&rsquo;s animatronic hand.",
        'links' => array(
            array('Control a servo with Arduino (official)', 'https://docs.arduino.cc/learn/electronics/servo-motors/'),
            array('Servo motor deep-dive (Last Minute Engineers)', 'https://lastminuteengineers.com/servo-motor-arduino-tutorial/'),
        ),
    ),
    array(
        'title' => 'Electronics &amp; Soldering',
        'icon' => 'thin-icon-box',
        'body' => "Before the code, you need a circuit that works. Learn to use a breadboard, read a simple schematic, and solder a clean joint &mdash; the hands-on skills behind every project. (We cover all of this in our workshops too.)",
        'links' => array(
            array('SparkFun: How to Solder (through-hole)', 'https://learn.sparkfun.com/tutorials/how-to-solder-through-hole-soldering'),
            array('SparkFun: How to Use a Breadboard', 'https://learn.sparkfun.com/tutorials/how-to-use-a-breadboard'),
        ),
    ),
    array(
        'title' => 'CAD &amp; 3D Printing',
        'icon' => 'thin-icon-chart',
        'body' => "Design custom parts &mdash; brackets, mounts, gripper fingers &mdash; and print them on our <a href=\"/projects\">printer fleet</a>. Onshape is free for students and runs in the browser, so you can start modeling today.",
        'links' => array(
            array('Onshape self-paced CAD courses (free)', 'https://learn.onshape.com/'),
            array('3D-printing basics that work (MatterHackers)', 'https://www.matterhackers.com/articles/how-to-succeed-when-3d-printing-with-pla'),
        ),
    ),
);
?>
<style>
    .learn-hero { padding: 60px 0 10px; text-align: center; }
    .learn-hero p { max-width: 720px; margin: 12px auto 0; color: #555; font-size: 17px; line-height: 1.6; }
    .learn-grid { max-width: 1040px; margin: 0 auto; padding: 30px 15px 40px; }
    .learn-card { background: #fff; border: 1px solid #ececec; border-radius: 10px; padding: 26px 28px; height: 100%; box-shadow: 0 3px 14px rgba(0,0,0,.05); display: flex; flex-direction: column; }
    .learn-card .icon { color: #24c57c; }
    .learn-card h3 { margin: 12px 0 10px; font-size: 21px; }
    .learn-card > p { color: #444; line-height: 1.6; font-size: 15px; margin: 0 0 14px; }
    .learn-card code { background: #f2f4f2; padding: 1px 5px; border-radius: 3px; font-size: 13px; color: #1f6b46; }
    .learn-links { list-style: none; padding: 0; margin: auto 0 0; border-top: 1px solid #eee; padding-top: 12px; }
    .learn-links li { margin-bottom: 8px; line-height: 1.4; }
    .learn-links a { color: #24c57c; font-weight: 600; font-size: 14px; text-decoration: none; }
    .learn-links a:hover { text-decoration: underline; }
    .learn-links a::before { content: "\2192"; margin-right: 6px; }
    .learn-cell { margin-bottom: 20px; }
    .learn-cta { text-align: center; padding: 10px 15px 60px; }
    .learn-cta p { color: #555; margin-bottom: 16px; }
</style>
<main class="page-content">
    <section class="learn-hero">
        <div class="shell">
            <h1>Learn Robotics</h1>
            <p>New to robotics? Start here. These are the components and skills we use most, each with hand-picked free tutorials you can work through at home &mdash; then come to a workshop and build the real thing with us.</p>
        </div>
    </section>
    <div class="learn-grid">
        <div class="range">
            <?php foreach ($guides as $g): ?>
                <div class="cell-md-6 cell-lg-4 learn-cell">
                    <div class="learn-card">
                        <span class="icon icon-lg <?php echo htmlspecialchars($g['icon']); ?>"></span>
                        <h3><?php echo $g['title']; ?></h3>
                        <p><?php echo $g['body']; ?></p>
                        <ul class="learn-links">
                            <?php foreach ($g['links'] as $l): ?>
                                <li><a href="<?php echo htmlspecialchars($l[1]); ?>" target="_blank" rel="noopener"><?php echo $l[0]; ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="learn-cta">
        <p>Working through these on your own is a great start &mdash; but the fastest way to learn is alongside people who&rsquo;ve done it before. Come to a workshop and jump in.</p>
        <a href="/join/discord" class="btn btn-primary">Join us on Discord</a>
        &nbsp;
        <a href="/events" class="btn btn-default">See upcoming events</a>
    </div>
</main>
<?php
footer();
