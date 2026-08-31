<?php
require('../../template/top.php');
head('Electronics Kit preorder received', true);

// No Discord alert here — this page fires on any visit. The authoritative alert +
// receipt come from the payment handler (paypal/ipn/handlers/kit.php) when the
// payment actually records.

result_card([
    'status'   => 'success',
    'title'    => 'Preorder received!',
    'subtitle' => 'Thanks for preordering an Electronics Kit.',
    'lead'     => 'Your payment is confirmed and a receipt is on its way to your inbox. We&rsquo;ll assemble your kit and email you the moment it&rsquo;s ready to pick up &mdash; kits are handed out at our general meetings.',
    'buttons'  => [
        ['href' => '/events', 'label' => 'See meeting times'],
        ['href' => '/join/discord', 'label' => 'Join our Discord', 'ghost' => true],
    ],
    'note'     => 'Questions? <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>',
]);

footer();
