<?php
require('../../template/top.php');
require_once(BASE . '/api/discord/bots/admin.php');
head('Thank you for your donation', true);

AdminBot::send_message("Donation received (probably)!");

result_card([
    'status'   => 'success',
    'title'    => 'Donation Received',
    'subtitle' => 'Thank you for supporting UNT Robotics.',
    'lead'     => 'Your gift goes directly to helping us support, teach and mentor young engineers at UNT &mdash; funding our robots, competitions and workshops. A receipt is on its way to your inbox.',
    'buttons'  => [
        ['href' => '/', 'label' => 'Back to home'],
        ['href' => '/sponsorships', 'label' => 'More ways to help', 'ghost' => true],
    ],
    'note'     => 'Questions? <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>',
]);

footer();
