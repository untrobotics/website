<?php
require('../template/top.php');
head('Unsubscribe', true);

$email = isset($_GET['e']) ? trim($_GET['e']) : '';
$token = isset($_GET['t']) ? $_GET['t'] : '';
$ok = false;

if ($email !== '' && $token !== '' && hash_equals(newsletter_unsub_token($email), $token)) {
    $db->query('UPDATE newsletter_signups SET unsubscribed = 1 WHERE email = "' . $db->real_escape_string($email) . '"');
    $ok = true;

    // Best-effort: blacklist the contact in Brevo so campaigns skip them there too.
    if (defined('BREVO_API_KEY') && BREVO_API_KEY !== '') {
        $ch = curl_init('https://api.brevo.com/v3/contacts/' . urlencode($email));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('api-key: ' . BREVO_API_KEY, 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('emailBlacklisted' => true)));
        curl_exec($ch);
        curl_close($ch);
    }
}
?>
<?php
if ($ok) {
    result_card([
        'status'   => 'success',
        'title'    => "You're Unsubscribed",
        'subtitle' => "You won't hear from our newsletter again.",
        'lead'     => htmlspecialchars($email) . ' has been removed from our newsletter. Changed your mind? You can sign up again any time on our home page.',
        'button'   => ['href' => '/', 'label' => 'Back to home'],
    ]);
} else {
    result_card([
        'status'   => 'error',
        'title'    => 'Link Not Valid',
        'subtitle' => 'We couldn&rsquo;t process this request.',
        'lead'     => 'This unsubscribe link is invalid or has expired. If you keep receiving emails you&rsquo;d rather not, contact <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a> and we&rsquo;ll take care of it.',
        'button'   => ['href' => '/', 'label' => 'Back to home'],
    ]);
}

footer();
