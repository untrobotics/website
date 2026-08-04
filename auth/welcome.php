<?php
require('../template/top.php');
head('Welcome', true, true);

result_card([
    'status'   => 'success',
    'title'    => 'Welcome to UNT Robotics!',
    'subtitle' => 'Your account is ready.',
    'lead'     => 'You&rsquo;re in. Two quick steps to get the most out of your membership:',
    'rows_label' => 'Get set up',
    'rows'     => [
        ['1 · Discord', 'Chat, events &amp; announcements'],
        ['2 · CampusLabs', 'Your official UNT membership'],
    ],
    'buttons'  => [
        ['href' => '/join/discord', 'label' => 'Join us on Discord'],
        ['href' => '/join/campuslabs', 'label' => 'Become a member', 'ghost' => true],
    ],
]);

footer();
