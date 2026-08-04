<?php
require('../template/top.php');
head('Dues Paid', true);

result_card([
    'status'   => 'success',
    'title'    => 'Dues Paid!',
    'subtitle' => 'Thank you for paying your dues.',
    'lead'     => 'Your membership is all set. One last step so you get your member role and access:',
    'button'   => ['href' => '/join/w/discord', 'label' => 'Sync my Discord'],
    'note'     => 'Questions? <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>',
]);

footer();
