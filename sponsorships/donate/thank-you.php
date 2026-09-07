<?php
require('../../template/top.php');
head('Thank you for your donation', true);

// NOTE: this page does NOT post a Discord alert. It's just the donor's landing
// page and fires on any visit (direct navigation, crawlers, abandoned checkouts),
// so alerting here produced false "donation received" pings. The authoritative
// alert comes from the webhook/IPN handler (paypal/ipn/handlers/donation.php),
// which fires only when a real payment is recorded, with the amount + donor.

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
