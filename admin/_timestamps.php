<?php
/*
 * Shared admin timestamp rendering: show every stored time in BOTH UTC and the
 * viewer's local time.
 *
 * DB TIMESTAMP columns are stored/read as UTC (the DB session tz is UTC). We
 * parse them explicitly as UTC here rather than via date()/strtotime(), because
 * template/top.php switches PHP's default tz to the logged-in admin's saved
 * timezone — which would make strtotime() misread the UTC string. The UTC label
 * is rendered server-side; the local line is filled in by admin_ts_script()
 * from the browser's own timezone (the most reliable "user's local time").
 */

/**
 * Render a MySQL UTC datetime as a two-line block: the UTC time (server-side)
 * plus a placeholder the client script fills with the viewer's local time.
 * Returns '' for empty/zero timestamps.
 */
function admin_ts($mysql_datetime, $fmt = 'M j, Y g:ia') {
    if (empty($mysql_datetime) || $mysql_datetime === '0000-00-00 00:00:00') {
        return '';
    }
    try {
        $dt = new DateTime($mysql_datetime, new DateTimeZone('UTC'));
    } catch (Exception $e) {
        return htmlspecialchars($mysql_datetime);
    }
    $iso = $dt->format('Y-m-d\TH:i:s\Z');
    $utc = $dt->format($fmt);
    return '<span class="admin-ts" data-utc="' . htmlspecialchars($iso, ENT_QUOTES) . '">'
        . '<span class="ts-utc">' . htmlspecialchars($utc) . ' UTC</span>'
        . '</span>';
}

/**
 * Emit the CSS + JS that fills every .admin-ts on the page with the viewer's
 * local time. Prints once per request; call it once before the footer on any
 * admin page that uses admin_ts().
 */
function admin_ts_script() {
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    ?>
<style>
    .admin-ts .ts-utc { display: block; white-space: nowrap; }
    .admin-ts .ts-local { display: block; white-space: nowrap; color: #8a908c; font-size: .85em; }
</style>
<script>
(function () {
    function fmtLocal(d) {
        var date = d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        var time = d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' });
        return date + ' ' + time;
    }
    document.querySelectorAll('.admin-ts[data-utc]').forEach(function (el) {
        if (el.querySelector('.ts-local')) { return; }
        var d = new Date(el.getAttribute('data-utc'));
        if (isNaN(d.getTime())) { return; }
        var s = document.createElement('span');
        s.className = 'ts-local';
        s.textContent = fmtLocal(d) + ' (your time)';
        el.appendChild(s);
    });
})();
</script>
    <?php
}
