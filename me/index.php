<?php
require('../template/top.php');
head('My Profile', true, true);

global $userinfo, $untrobotics, $timezones;

// CSRF token for the profile/password forms (self-contained; no token system existed).
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// grad_term is stored as an index into this array (spring=0, fall=1, summer=2),
// matching how registration (auth/join.php) writes it.
$valid_grad_terms = array('spring', 'fall', 'summer');
$current_term_index = (int)$userinfo['grad_term'];

$current_year = (int)date('Y');
$grad_year = (int)$userinfo['grad_year'];

$is_linked_discord = !empty($userinfo['discord_id']);
$good_standing = $untrobotics->is_user_in_good_standing($userinfo);

// Dues payment history for this user.
$dues_history = array();
$dq = $db->query('SELECT amount, dues_term, dues_year, payment_timestamp, refunded FROM dues_payments WHERE uid = "' . $db->real_escape_string($userinfo['id']) . '" ORDER BY payment_timestamp DESC');
if ($dq) { while ($r = $dq->fetch_assoc()) { $dues_history[] = $r; } }

// Merch/Printful order history for this user (uid was added to printful_order).
$order_history = array();
$oq = $db->query('SELECT order_id, order_name, order_variant_name, confirmed, refunded FROM printful_order WHERE uid = "' . $db->real_escape_string($userinfo['id']) . '" ORDER BY id DESC');
if ($oq) { while ($r = $oq->fetch_assoc()) { $order_history[] = $r; } }

// Discord OAuth authorize URL (the /auth/discord callback consumes ?code).
$discord_link_url = DISCORD_APP_API_URL . '/oauth2/authorize?' . http_build_query(array(
    'client_id' => DISCORD_APP_CLIENT_ID,
    'redirect_uri' => DISCORD_APP_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'identify guilds.join',
));

function e($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }

// dues_term is stored as a Semester constant value (see auth/join.php / dues handler).
function dues_term_label($term, $year) {
    $name = class_exists('Semester') ? Semester::get_name_from_value((int)$term) : null;
    return ($name ? ucfirst(strtolower($name)) : ('Term ' . (int)$term)) . ' ' . (int)$year;
}
?>

<style>
/* This is an edit form with pre-filled fields, so use plain static labels above
   each control. The theme's floating .form-label overlaps pre-filled <select>s
   (timezone / graduation), which is the display bug. */
#profile-form .form-label,
#password-form .form-label {
    position: static !important;
    display: block;
    transform: none !important;
    top: auto !important; left: auto !important;
    font-size: 12px; letter-spacing: 0.03em; text-transform: none;
    color: #8a8a8a; margin: 0 0 5px; pointer-events: auto; opacity: 1;
}
#profile-form .form-control, #password-form .form-control { margin-top: 0; }
/* The theme removes native select rendering, which vertically clips the selected
   value. Restore it and give the box room. */
#profile-form select.form-control {
    height: auto; min-height: 46px; padding: 10px 14px; line-height: 1.4;
    border: 1px solid #e1e1e1; border-radius: 4px; background-color: #fff; color: #333;
    -webkit-appearance: menulist; -moz-appearance: menulist; appearance: menulist;
}
.sms-toggle { display: flex; align-items: flex-start; gap: 10px; font-weight: normal; font-size: 0.9rem; line-height: 1.5; color: #555; cursor: pointer; }
.sms-toggle input { margin-top: 4px; flex: 0 0 auto; }
</style>

<main class="page-content">
    <section class="section-50 section-md-66">
        <div class="shell text-left">
            <div class="range range-md-center">
                <div class="cell-lg-10 cell-xl-9">

                    <div class="range range-xs-middle offset-bottom-20">
                        <div class="cell-sm-8">
                            <h1 class="offset-top-0">My Profile</h1>
                            <p class="text-gray-dark">Manage your UNT Robotics account details.</p>
                        </div>
                        <div class="cell-sm-4 text-sm-right">
                            <?php if ($good_standing): ?>
                                <span class="label" style="background:#24c57c;color:#fff;padding:8px 14px;border-radius:20px;display:inline-block;font-size:13px;">
                                    <span class="mdi mdi-check-circle"></span> Good Standing
                                </span>
                            <?php else: ?>
                                <a href="/dues" class="label" style="background:#f0ad4e;color:#fff;padding:8px 14px;border-radius:20px;display:inline-block;font-size:13px;text-decoration:none;">
                                    <span class="mdi mdi-alert-circle-outline"></span> Dues not paid &mdash; Pay now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ACCOUNT (read-only) -->
                    <div class="panel panel-default offset-bottom-30">
                        <div class="panel-heading"><strong>Account</strong></div>
                        <div class="panel-body">
                            <div class="range">
                                <div class="cell-sm-6 offset-bottom-20">
                                    <small class="text-gray">UNT EUID</small>
                                    <div><strong><?php echo e($userinfo['unteuid']); ?></strong></div>
                                </div>
                                <div class="cell-sm-6 offset-bottom-20">
                                    <small class="text-gray">Member since</small>
                                    <div><strong><?php echo e(date('F j, Y', strtotime($userinfo['reg_timestamp']))); ?></strong></div>
                                </div>
                                <div class="cell-sm-12">
                                    <small class="text-gray">Discord</small>
                                    <div>
                                        <?php if ($is_linked_discord): ?>
                                            <strong style="color:#24c57c;"><span class="mdi mdi-check-circle"></span> Linked</strong>
                                            &nbsp;<a href="<?php echo e($discord_link_url); ?>" class="text-primary">Re-link</a>
                                        <?php else: ?>
                                            <a href="<?php echo e($discord_link_url); ?>" class="btn btn-sm btn-default">
                                                <span class="mdi mdi-discord"></span> Link your Discord
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <p class="text-gray offset-top-10" style="font-size:12px;margin-bottom:0;">
                                Your EUID is set at registration and can't be changed here &mdash; contact an officer if it's wrong.
                            </p>
                        </div>
                    </div>

                    <!-- PROFILE DETAILS (editable) -->
                    <div class="panel panel-default offset-bottom-30">
                        <div class="panel-heading"><strong>Profile details</strong></div>
                        <div class="panel-body">
                            <div class="profile-feedback offset-bottom-20" hidden></div>
                            <form id="profile-form" method="post" action="/ajax/update-profile" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                <div class="range">
                                    <div class="cell-md-6 form-group">
                                        <label for="name" class="form-label">Full name</label>
                                        <input id="name" name="name" type="text" class="form-control" value="<?php echo e($userinfo['name']); ?>" required minlength="4">
                                    </div>
                                    <div class="cell-md-6 form-group">
                                        <label for="email" class="form-label">Email</label>
                                        <input id="email" name="email" type="email" class="form-control" value="<?php echo e($userinfo['email']); ?>" required>
                                    </div>
                                    <div class="cell-md-6 form-group">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input id="phone" name="phone" type="tel" class="form-control" value="<?php echo e($userinfo['phone']); ?>" required>
                                    </div>
                                    <div class="cell-md-6 form-group">
                                        <label for="timezone" class="form-label">Timezone</label>
                                        <select id="timezone" name="timezone" class="form-control">
                                            <?php foreach ($timezones as $tz): ?>
                                                <option value="<?php echo e($tz); ?>" <?php echo ($tz === $userinfo['timezone']) ? 'selected' : ''; ?>><?php echo e($tz); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="cell-md-6 form-group">
                                        <label for="grad_term" class="form-label">Graduation term</label>
                                        <select id="grad_term" name="grad_term" class="form-control">
                                            <?php foreach ($valid_grad_terms as $i => $term): ?>
                                                <option value="<?php echo $i; ?>" <?php echo ($i === $current_term_index) ? 'selected' : ''; ?>><?php echo ucfirst($term); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="cell-md-6 form-group">
                                        <label for="grad_year" class="form-label">Graduation year</label>
                                        <select id="grad_year" name="grad_year" class="form-control">
                                            <?php
                                            // Offer current year .. +7, and include the user's stored year if older.
                                            $years = range($current_year, $current_year + 7);
                                            if ($grad_year && !in_array($grad_year, $years)) array_unshift($years, $grad_year);
                                            foreach ($years as $y): ?>
                                                <option value="<?php echo $y; ?>" <?php echo ($y === $grad_year) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="cell-md-12 form-group offset-top-10">
                                        <label class="sms-toggle">
                                            <input type="checkbox" name="sms_consent" value="1" <?php echo !empty($userinfo['sms_consent']) ? 'checked' : ''; ?>>
                                            <span>Receive SMS text messages from UNT Robotics &mdash; account/phone verification codes and replies to questions you text us. Optional; message &amp; data rates may apply. Reply STOP to opt out or HELP for help.</span>
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-default">Save changes</button>
                            </form>
                        </div>
                    </div>

                    <!-- DUES HISTORY -->
                    <div class="panel panel-default offset-bottom-30">
                        <div class="panel-heading"><strong>Dues payment history</strong></div>
                        <div class="panel-body">
                            <?php if (empty($dues_history)): ?>
                                <p class="text-gray" style="margin:0;">No dues payments on record yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table" style="margin:0;">
                                        <thead><tr><th>Date</th><th>Semester</th><th class="text-right">Amount</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($dues_history as $d): ?>
                                            <tr<?php echo !empty($d['refunded']) ? ' class="text-gray"' : ''; ?>>
                                                <td><?php echo e(date('M j, Y', strtotime($d['payment_timestamp']))); ?></td>
                                                <td><?php echo e(dues_term_label($d['dues_term'], $d['dues_year'])); ?></td>
                                                <td class="text-right"><?php if (!empty($d['refunded'])): ?><span style="text-decoration:line-through;">$<?php echo e(number_format((float)$d['amount'], 2)); ?></span> <span class="label label-warning">Refunded</span><?php else: ?>$<?php echo e(number_format((float)$d['amount'], 2)); ?><?php endif; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ORDER HISTORY -->
                    <div class="panel panel-default offset-bottom-30">
                        <div class="panel-heading"><strong>Merch order history</strong></div>
                        <div class="panel-body">
                            <?php if (empty($order_history)): ?>
                                <p class="text-gray" style="margin:0;">No merch orders on record yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table" style="margin:0;">
                                        <thead><tr><th>Item</th><th>Order #</th><th class="text-right">Status</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($order_history as $o): ?>
                                            <tr<?php echo !empty($o['refunded']) ? ' class="text-gray"' : ''; ?>>
                                                <td><?php echo e($o['order_variant_name'] ?: $o['order_name']); ?></td>
                                                <td><?php echo e($o['order_id']); ?></td>
                                                <td class="text-right"><?php echo !empty($o['refunded']) ? '<span class="label label-warning">Refunded</span>' : ($o['confirmed'] ? '<span style="color:#24c57c;">Confirmed</span>' : '<span class="text-gray">Pending</span>'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- PASSWORD -->
                    <div class="panel panel-default offset-bottom-30">
                        <div class="panel-heading"><strong>Change password</strong></div>
                        <div class="panel-body">
                            <div class="password-feedback offset-bottom-20" hidden></div>
                            <form id="password-form" method="post" action="/ajax/update-password" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrf); ?>">
                                <div class="range">
                                    <div class="cell-md-4 form-group">
                                        <label for="current_password" class="form-label">Current password</label>
                                        <input id="current_password" name="current_password" type="password" class="form-control" autocomplete="current-password" required>
                                    </div>
                                    <div class="cell-md-4 form-group">
                                        <label for="new_password" class="form-label">New password</label>
                                        <input id="new_password" name="new_password" type="password" class="form-control" autocomplete="new-password" required minlength="8">
                                    </div>
                                    <div class="cell-md-4 form-group">
                                        <label for="confirm_password" class="form-label">Confirm new password</label>
                                        <input id="confirm_password" name="confirm_password" type="password" class="form-control" autocomplete="new-password" required minlength="8">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-default">Update password</button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</main>

<script>
(function () {
    function feedback(el, ok, message) {
        el.hidden = false;
        el.className = (el.className.replace(/alert[-a-z ]*/g, '').trim()) +
            ' alert ' + (ok ? 'alert-success' : 'alert-danger');
        el.textContent = message;
    }

    function wire(formId, feedbackSelector, opts) {
        var form = document.getElementById(formId);
        if (!form) return;
        var fb = form.parentNode.querySelector(feedbackSelector);
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            var label = btn.textContent;
            btn.disabled = true; btn.textContent = 'Saving…';
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(function (res) {
                feedback(fb, !!res.ok, res.message || (res.ok ? 'Saved.' : 'Something went wrong.'));
                if (res.ok && opts && opts.resetOnSuccess) form.reset();
            }).catch(function () {
                feedback(fb, false, 'A network error occurred. Please try again.');
            }).then(function () {
                btn.disabled = false; btn.textContent = label;
            });
        });
    }

    wire('profile-form', '.profile-feedback', { resetOnSuccess: false });
    wire('password-form', '.password-feedback', { resetOnSuccess: true });
})();
</script>

<?php
footer();
?>
