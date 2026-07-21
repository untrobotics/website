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
<main class="page-content">
    <section class="section-50">
        <div class="shell">
            <div class="range range-md-justify">
                <div class="cell-md-10 cell-lg-8 text-center">
                    <?php if ($ok): ?>
                        <h1>You&rsquo;re unsubscribed</h1>
                        <p><?php echo htmlspecialchars($email); ?> will no longer receive our newsletter. Changed your mind? You can sign up again any time on our home page.</p>
                    <?php else: ?>
                        <h1>Link not valid</h1>
                        <p>This unsubscribe link is invalid or has expired. If you keep receiving emails you&rsquo;d rather not, contact <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a> and we&rsquo;ll take care of it.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
<?php
footer();
