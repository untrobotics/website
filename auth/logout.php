<?php
require("../template/top.php");
if (isset($_COOKIE[COOKIE_PREFIX . '_SESSION_ID']) || isset($_COOKIE[COOKIE_PREFIX . '_SESSION_NAME'])) {
	setcookie(COOKIE_PREFIX . '_SESSION_ID', false, 1, '/', WEBSITE_DOMAIN, true, true);
	setcookie(COOKIE_PREFIX . '_SESSION_NAME', false, 1, '/', WEBSITE_DOMAIN, true, true);

	$db->query("DELETE FROM auth_sessions WHERE session_id = '" . $db->real_escape_string($_COOKIE[COOKIE_PREFIX . '_SESSION_ID']) . "' OR session_name = '" . $db->real_escape_string($_COOKIE[COOKIE_PREFIX . 'SESSION_NAME']) . "' LIMIT 1");
}

session_regenerate_id();
session_unset();
session_destroy();

head('Logged Out', true);
?>

<main class="page-content">
        <section class="section-50">
          <div class="shell">
            <div class="range range-md-justify">
              <div class="cell-md-12">
                <div class="inset-md-right-30 inset-lg-right-0 text-center">
                  <h2>You have been logged out</h2>
		  <p><a href='/auth/login'>Click here</a> to log in again.</p>
                </div>
              </div>
            </div>
          </div>
        </section>
</main>

<?php
footer();
?>
