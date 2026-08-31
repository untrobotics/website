<?php
/*
 * Shared admin UI kit — include right after head() on every /admin page, then
 * lay the page out with .admin-wrap / .admin-head / .admin-stats / .admin-card /
 * .admin-table, status pills via admin_pill(), and stat cards via admin_stat().
 */

if (!function_exists('admin_pill')) {
    /**
     * Render a status as a coloured pill. Known statuses get a tone + tidy label;
     * anything else falls back to a neutral pill with a humanised label.
     */
    function admin_pill($status, $label = null) {
        $tone = array(
            'paid' => 'paid', 'ready' => 'ready', 'picked_up' => 'picked_up', 'refunded' => 'refunded',
            'pending' => 'info', 'approved' => 'ready', 'denied' => 'refunded',
            'draft' => 'neutral', 'sending' => 'info', 'paused' => 'paid', 'sent' => 'ready',
            'done' => 'ready', 'queued' => 'info', 'yes' => 'ready', 'no' => 'refunded',
        );
        $labels = array(
            'paid' => 'Paid', 'ready' => 'Ready', 'picked_up' => 'Picked up', 'refunded' => 'Refunded',
            'pending' => 'Pending', 'approved' => 'Approved', 'denied' => 'Denied', 'draft' => 'Draft',
            'sending' => 'Sending', 'paused' => 'Paused', 'sent' => 'Sent', 'done' => 'Done', 'queued' => 'Queued',
        );
        $key = strtolower(trim((string) $status));
        $variant = isset($tone[$key]) ? $tone[$key] : 'neutral';
        if ($label === null) {
            $label = isset($labels[$key]) ? $labels[$key] : ucwords(str_replace('_', ' ', $key));
        }
        return '<span class="pill pill-' . $variant . '">' . htmlspecialchars($label) . '</span>';
    }
}

if (!function_exists('admin_stat')) {
    /** A single stat card. $tone: '' | green | amber | grey | red. */
    function admin_stat($num, $label, $tone = '') {
        return '<div class="stat-card' . ($tone ? ' ' . $tone : '') . '">'
            . '<span class="num">' . htmlspecialchars((string) $num) . '</span>'
            . '<span class="lbl">' . htmlspecialchars($label) . '</span></div>';
    }
}
?>
<style>
  .admin-wrap { max-width: 1120px; margin: 0 auto; padding: 0 4px; }
  .admin-head { margin: 8px 0 22px; }
  .admin-head h1 { font-size: 30px; margin: 0 0 6px; line-height: 1.15; }
  .admin-head .lead { color: #6b716d; font-size: 15px; margin: 0; max-width: 820px; line-height: 1.5; }
  .admin-back { display: inline-block; font-size: 13px; color: #8a908c; margin-bottom: 10px; }
  .admin-back:hover { color: #1a8a4f; }
  .admin-section-title { font-size: 18px; font-weight: 700; margin: 26px 0 12px; color: #1f2421; }

  /* Stat cards */
  .admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin: 0 0 24px; }
  .stat-card { background: #fff; border: 1px solid #e6e9e7; border-radius: 12px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,.04); }
  .stat-card .num { font-size: 30px; font-weight: 800; line-height: 1; color: #1f2421; font-variant-numeric: tabular-nums; }
  .stat-card .lbl { display: block; margin-top: 7px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .05em; color: #8a908c; }
  .stat-card.green .num { color: #1a8a4f; }
  .stat-card.amber .num { color: #c07800; }
  .stat-card.grey .num  { color: #6b716d; }
  .stat-card.red .num   { color: #a12622; }

  /* Status pills */
  .pill { display: inline-block; padding: 3px 11px; border-radius: 999px; font-size: 11.5px; font-weight: 700; letter-spacing: .02em; line-height: 1.55; white-space: nowrap; }
  .pill-paid      { background: #fff3d6; color: #8a5a00; }
  .pill-ready     { background: #d8f5e3; color: #12703f; }
  .pill-picked_up { background: #e7eae8; color: #555b57; }
  .pill-refunded  { background: #fde2e1; color: #a12622; }
  .pill-neutral   { background: #e7eae8; color: #555b57; }
  .pill-info      { background: #e2eefb; color: #1c5aa8; }

  /* Help / legend box */
  .admin-help { background: #eef4f1; border: 1px solid #d7e6dd; border-radius: 10px; padding: 13px 16px; margin: 0 0 22px; font-size: 13.5px; color: #39463f; line-height: 1.55; }
  .admin-help strong { color: #1a5c39; }
  .admin-help ul { margin: 8px 0 0; padding-left: 18px; }
  .admin-help li { margin: 4px 0; }

  /* Card container (replaces bootstrap .panel) */
  .admin-card { background: #fff; border: 1px solid #e6e9e7; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.04); margin-bottom: 22px; overflow: hidden; }
  .admin-card > .hd { padding: 14px 18px; border-bottom: 1px solid #eef1ef; font-weight: 700; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
  .admin-card > .hd .sub { font-weight: 400; color: #8a908c; font-size: 13px; }
  .admin-card > .bd { padding: 16px 18px; }
  .admin-card > .bd.flush { padding: 0; }

  /* Polished tables */
  .admin-card .admin-table-wrap { overflow-x: auto; }
  .admin-table { width: 100%; border-collapse: collapse; }
  .admin-table th { text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #8a908c; padding: 11px 14px; border-bottom: 2px solid #eef1ef; white-space: nowrap; background: #fbfcfb; }
  .admin-table td { padding: 12px 14px; border-bottom: 1px solid #f0f2f1; vertical-align: middle; font-size: 14px; }
  .admin-table tr:last-child td { border-bottom: 0; }
  .admin-table tbody tr:hover td { background: #fafbfa; }
  .admin-table .muted { color: #9aa0a6; font-size: 12px; }
  .admin-table tr.is-dim td { opacity: .55; }
  .admin-table .num { font-variant-numeric: tabular-nums; }
  .admin-empty { padding: 30px 14px; text-align: center; color: #9aa0a6; }

  /* Action buttons */
  .admin-actions { display: flex; flex-wrap: wrap; gap: 8px; }
  .admin-actions form { display: inline; margin: 0; }
  .btn-pill { display: inline-block; border: 0; border-radius: 999px; padding: 7px 15px; font-size: 12.5px; font-weight: 600; cursor: pointer; text-decoration: none; line-height: 1.3; }
  .btn-pill.go { background: #1a8a4f; color: #fff; }
  .btn-pill.go:hover { background: #157540; color: #fff; }
  .btn-pill.neutral { background: #eceeed; color: #444; }
  .btn-pill.neutral:hover { background: #e0e3e1; }
  .btn-pill.danger { background: #fbe4e3; color: #a12622; }
  .btn-pill.danger:hover { background: #f6cbcb; }

  /* Forms */
  .admin-form .fld { margin-bottom: 14px; }
  .admin-form label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 5px; color: #39463f; }
  .admin-form input[type=text], .admin-form input[type=number], .admin-form input[type=email], .admin-form textarea, .admin-form select {
      width: 100%; max-width: 520px; padding: 9px 11px; border: 1px solid #d6dbd8; border-radius: 8px; font-size: 14px; background: #fff; color: #1f2421;
  }
  .admin-form textarea { max-width: 100%; resize: vertical; line-height: 1.5; }
  .admin-form input:focus, .admin-form textarea:focus, .admin-form select:focus { outline: none; border-color: #45cd8f; box-shadow: 0 0 0 3px rgba(69,205,143,.18); }
  .admin-form .row-inline { display: flex; align-items: flex-end; gap: 12px; flex-wrap: wrap; }
  .admin-form .row-inline .fld { margin-bottom: 0; }
  .btn-solid { display: inline-block; border: 0; border-radius: 8px; padding: 10px 18px; font-size: 14px; font-weight: 700; cursor: pointer; }
  .btn-solid.go { background: #1a8a4f; color: #fff; }
  .btn-solid.go:hover { background: #157540; }
  .btn-solid.primary { background: #45cd8f; color: #10361f; }
  .btn-solid.primary:hover { background: #37bd80; }

  /* Notices */
  .admin-notice { border-radius: 10px; padding: 12px 16px; margin: 0 0 20px; font-size: 14px; border: 1px solid transparent; }
  .admin-notice.ok  { background: #d8f5e3; border-color: #b6e6c9; color: #12703f; }
  .admin-notice.err { background: #fde2e1; border-color: #f5c6c4; color: #a12622; }

  /* Hub (admin home) */
  .admin-hub { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
  .hub-card { display: block; background: #fff; border: 1px solid #e6e9e7; border-radius: 12px; padding: 18px 20px; box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: border-color .12s, box-shadow .12s, transform .12s; }
  .hub-card:hover { border-color: #45cd8f; box-shadow: 0 4px 14px rgba(0,0,0,.07); transform: translateY(-1px); text-decoration: none; }
  .hub-card .hc-title { font-size: 17px; font-weight: 700; color: #1a8a4f; margin: 0 0 6px; }
  .hub-card .hc-desc { font-size: 13.5px; color: #6b716d; margin: 0; line-height: 1.5; }
</style>
