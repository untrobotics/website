<?php
/**
 * Shared "result card" for post-action landing pages — order confirmed, dues paid,
 * donation received, unsubscribed, logged out, and so on. Keeps these one-off pages
 * from reading as a bare wall of centered text. Emits its CSS once per request.
 *
 * Usage (call between head() and footer(); it renders its own <main>):
 *   result_card([
 *     'status'   => 'success' | 'wait' | 'error',   // header colour + icon
 *     'title'    => 'Merch Ordered!',
 *     'subtitle' => 'Thank you for your order.',
 *     'lead'     => 'Intro sentence (HTML allowed).',
 *     'rows_label' => 'Order summary',
 *     'rows'     => [ ['Item', 'Bomber Jacket'], ['Quantity', '1'] ], // values are HTML
 *     'total'    => ['Amount paid', '$65.00'],        // emphasised final row
 *     'address'  => '<strong>Name</strong><br>1 Main St<br>City, TX',
 *     'address_label' => 'Shipping to',
 *     'extra'    => '<p>…</p>',                        // arbitrary extra body HTML
 *     'button'   => ['href' => '/merch', 'label' => 'Continue shopping'],
 *     'note'     => 'Questions? <a href="mailto:hello@untrobotics.com">hello@untrobotics.com</a>',
 *   ]);
 *
 * Row/address/lead/extra/note values are emitted as HTML, so the CALLER escapes any
 * user-supplied text (htmlspecialchars). Title and subtitle are escaped here.
 */
function result_card(array $o) {
    static $css_done = false;

    $status = isset($o['status']) ? $o['status'] : 'success';
    $icons = array(
        'success' => '<polyline points="20 6 9 17 4 12"></polyline>',
        'wait'    => '<circle cx="12" cy="12" r="9"></circle><polyline points="12 7 12 12 15 14"></polyline>',
        'error'   => '<circle cx="12" cy="12" r="9"></circle><line x1="12" y1="8" x2="12" y2="12.5"></line><line x1="12" y1="16" x2="12" y2="16"></line>',
    );
    if (!isset($icons[$status])) {
        $status = 'success';
    }
    $topclass = $status === 'wait' ? ' is-wait' : ($status === 'error' ? ' is-error' : '');

    if (!$css_done) {
        $css_done = true;
        ?>
<style>
    .rc-wrap { max-width: 560px; margin: 0 auto; padding: 0 16px; }
    .rc-card { background: #fff; border: 1px solid #e7ebe8; border-radius: 18px;
        box-shadow: 0 18px 50px rgba(17,24,39,.09); overflow: hidden; text-align: left; }
    .rc-top { padding: 40px 34px 32px; text-align: center; color: #fff;
        background: linear-gradient(135deg, #00a24a 0%, #00853e 100%); }
    .rc-top.is-wait { background: linear-gradient(135deg, #4b5563 0%, #374151 100%); }
    .rc-top.is-error { background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%); }
    .rc-badge { width: 70px; height: 70px; border-radius: 50%; margin: 0 auto 16px;
        background: rgba(255,255,255,.16); border: 2px solid rgba(255,255,255,.55);
        display: flex; align-items: center; justify-content: center; }
    .rc-badge svg { width: 36px; height: 36px; stroke: #fff; stroke-width: 3;
        stroke-linecap: round; stroke-linejoin: round; fill: none; }
    .rc-top h1 { color: #fff; font-size: 30px; line-height: 1.1; margin: 0 0 6px; }
    .rc-top p { color: rgba(255,255,255,.92); font-size: 15px; margin: 0; }
    .rc-body { padding: 26px 34px 34px; }
    .rc-lead { text-align: center; color: #4b5563; font-size: 15px; line-height: 1.6; margin: 0 0 26px; }
    .rc-lead p { margin: 0 0 10px; } .rc-lead p:last-child { margin-bottom: 0; }
    .rc-label { text-transform: uppercase; letter-spacing: .12em; font-size: 12px;
        font-weight: 700; color: #00853e; margin: 0 0 4px; }
    .rc-row { display: flex; justify-content: space-between; gap: 18px; padding: 12px 0;
        border-bottom: 1px solid #eef1ef; font-size: 15px; }
    .rc-row:last-child { border-bottom: 0; }
    .rc-row .lbl { color: #6b7280; flex: 0 0 auto; }
    .rc-row .val { color: #1f2937; font-weight: 600; text-align: right; word-break: break-word; }
    .rc-row.rc-total .val { color: #00853e; font-size: 20px; }
    .rc-ship { margin-top: 26px; padding-top: 24px; border-top: 1px solid #eef1ef; }
    .rc-ship address { font-style: normal; color: #374151; line-height: 1.65; font-size: 15px; margin: 6px 0 0; }
    .rc-actions { margin-top: 30px; text-align: center; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .rc-btn { display: inline-block; background: #00853e; color: #fff !important;
        padding: 13px 30px; border-radius: 10px; font-weight: 600; text-decoration: none; transition: background .15s; }
    .rc-btn:hover { background: #006f34; }
    .rc-btn.is-ghost { background: transparent; color: #00853e !important; border: 1.5px solid #cfe6d8; }
    .rc-btn.is-ghost:hover { background: #f1f8f3; }
    .rc-note { text-align: center; color: #9ca3af; font-size: 13px; margin: 18px 0 0; }
    @media (max-width: 520px) {
        .rc-top { padding: 32px 22px 26px; } .rc-body { padding: 24px 22px 30px; }
        .rc-top h1 { font-size: 25px; }
    }
</style>
        <?php
    }
    ?>
<main class="page-content">
    <section class="section-50">
        <div class="rc-wrap">
            <div class="rc-card">
                <div class="rc-top<?php echo $topclass; ?>">
                    <span class="rc-badge"><svg viewBox="0 0 24 24" fill="none"><?php echo $icons[$status]; ?></svg></span>
                    <h1><?php echo htmlspecialchars($o['title']); ?></h1>
                    <?php if (!empty($o['subtitle'])) { ?><p><?php echo htmlspecialchars($o['subtitle']); ?></p><?php } ?>
                </div>
                <div class="rc-body">
                    <?php if (!empty($o['lead'])) { ?><div class="rc-lead"><?php echo $o['lead']; ?></div><?php } ?>
                    <?php if (!empty($o['rows']) || !empty($o['total'])) { ?>
                    <?php if (!empty($o['rows_label'])) { ?><p class="rc-label"><?php echo htmlspecialchars($o['rows_label']); ?></p><?php } ?>
                        <?php foreach ((isset($o['rows']) ? $o['rows'] : array()) as $r) { ?>
                        <div class="rc-row"><span class="lbl"><?php echo htmlspecialchars($r[0]); ?></span><span class="val"><?php echo $r[1]; ?></span></div>
                        <?php } ?>
                        <?php if (!empty($o['total'])) { ?>
                        <div class="rc-row rc-total"><span class="lbl"><?php echo htmlspecialchars($o['total'][0]); ?></span><span class="val"><?php echo $o['total'][1]; ?></span></div>
                        <?php } ?>
                    <?php } ?>
                    <?php if (!empty($o['address'])) { ?>
                    <div class="rc-ship">
                        <p class="rc-label"><?php echo htmlspecialchars(isset($o['address_label']) ? $o['address_label'] : 'Shipping to'); ?></p>
                        <address><?php echo $o['address']; ?></address>
                    </div>
                    <?php } ?>
                    <?php if (!empty($o['extra'])) { echo $o['extra']; } ?>
                    <?php if (!empty($o['button']) || !empty($o['buttons'])) { ?>
                    <div class="rc-actions">
                        <?php
                        $btns = !empty($o['buttons']) ? $o['buttons'] : array($o['button']);
                        foreach ($btns as $b) {
                            $ghost = !empty($b['ghost']) ? ' is-ghost' : '';
                            echo '<a class="rc-btn' . $ghost . '" href="' . htmlspecialchars($b['href']) . '">' . htmlspecialchars($b['label']) . '</a>';
                        }
                        ?>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php if (!empty($o['note'])) { ?><p class="rc-note"><?php echo $o['note']; ?></p><?php } ?>
        </div>
    </section>
</main>
    <?php
}
