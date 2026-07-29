<?php
require('template/top.php');
head('Learn', true);

$guides = array(
    array(
        'title' => 'Arduino',
        'icon' => 'thin-icon-pointer',
        'body' => "The Arduino is our go-to microcontroller for beginners &mdash; cheap, forgiving, and everywhere. Start by installing the free <strong>Arduino IDE</strong>, plugging in an Arduino Uno over USB, and running the <em>Blink</em> example to flash the onboard LED. From there you&rsquo;ll learn <code>digitalWrite</code> / <code>digitalRead</code> for switching pins, <code>analogRead</code> for sensors, and <code>Serial.println()</code> to see what your board is thinking. It&rsquo;s the fastest way to get a circuit doing something real.",
    ),
    array(
        'title' => 'Raspberry Pi',
        'icon' => 'thin-icon-study',
        'body' => "When a project needs real computing power &mdash; cameras, computer vision, ROS, networking &mdash; we reach for a <strong>Raspberry Pi</strong>. Flash Raspberry Pi OS to a microSD card with the Raspberry Pi Imager, boot it up, and you have a full Linux computer with GPIO pins. We often pair a Pi (the brains) with an Arduino (real-time motor/sensor control) on the same robot. Get comfortable with the Linux terminal and Python and you can do almost anything.",
    ),
    array(
        'title' => 'Motors &amp; the L298N Driver',
        'icon' => 'thin-icon-car',
        'body' => "A microcontroller can&rsquo;t power a motor directly &mdash; it can&rsquo;t supply enough current, and reversing direction needs an H-bridge. Our standard setup is a basic <strong>6V DC motor</strong> driven by an <strong>L298N motor driver</strong>. The L298N takes your battery power and a couple of control pins from the Arduino/Pi: direction pins set which way the motor spins, and a PWM signal on the enable pin sets the speed. One L298N drives two motors &mdash; perfect for a simple two-wheel robot.",
    ),
    array(
        'title' => 'Servos',
        'icon' => 'thin-icon-lightbulb',
        'body' => "Servos are motors that hold a precise angle &mdash; great for steering, arms, grippers, and anything that needs to move <em>to a position</em> rather than just spin. A hobby servo has three wires (power, ground, signal); you send it a position with one line of code (<code>servo.write(90)</code> using Arduino&rsquo;s Servo library). We use them all over our builds, from Sofabot&rsquo;s steering to Scrapp-E&rsquo;s animatronic hand.",
    ),
    array(
        'title' => 'Electronics &amp; Soldering',
        'icon' => 'thin-icon-box',
        'body' => "Before the code, you need a circuit that works. We run workshops on the basics &mdash; reading a schematic, using a breadboard, powering things safely, and <strong>soldering</strong> a solid joint. Once your prototype works on a breadboard, soldering it to a protoboard makes it permanent and reliable. These are the hands-on skills that carry over to every project.",
    ),
    array(
        'title' => 'CAD &amp; 3D Printing',
        'icon' => 'thin-icon-chart',
        'body' => "Need a custom bracket, a motor mount, or a whole chassis? We design parts in <strong>CAD</strong> (SolidWorks and free tools like Onshape/Fusion) and print them on our <a href=\"/projects\">printer fleet</a>. Learning to model a part and turn it into a physical object &mdash; nose cones, gearboxes, gripper fingers &mdash; is one of the most useful skills you&rsquo;ll pick up here.",
    ),
);
?>
<style>
    .learn-hero { padding: 60px 0 10px; text-align: center; }
    .learn-hero p { max-width: 720px; margin: 12px auto 0; color: #555; font-size: 17px; line-height: 1.6; }
    .learn-grid { max-width: 1040px; margin: 0 auto; padding: 30px 15px 40px; }
    .learn-card { background: #fff; border: 1px solid #ececec; border-radius: 10px; padding: 26px 28px; height: 100%; box-shadow: 0 3px 14px rgba(0,0,0,.05); }
    .learn-card .icon { color: #24c57c; }
    .learn-card h3 { margin: 12px 0 10px; font-size: 21px; }
    .learn-card p { color: #444; line-height: 1.65; font-size: 15px; margin: 0; }
    .learn-card code { background: #f2f4f2; padding: 1px 5px; border-radius: 3px; font-size: 13px; color: #1f6b46; }
    .learn-cell { margin-bottom: 20px; }
    .learn-cta { text-align: center; padding: 10px 15px 60px; }
    .learn-cta p { color: #555; margin-bottom: 16px; }
</style>
<main class="page-content">
    <section class="learn-hero">
        <div class="shell">
            <h1>Learn Robotics</h1>
            <p>New to robotics? Start here. These are the components and skills we use most often &mdash; and everything below is covered hands-on in our workshops, no experience required.</p>
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
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="learn-cta">
        <p>The best way to learn is to build alongside people who&rsquo;ve done it before. Come to a workshop and jump in.</p>
        <a href="/join/discord" class="btn btn-primary">Join us on Discord</a>
        &nbsp;
        <a href="/events" class="btn btn-default">See upcoming events</a>
    </div>
</main>
<?php
footer();
