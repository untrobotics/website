<?php
require('../template/top.php');
head('Botathon — Past Events', true);

// Season-by-season history. Newest first. Photos live in /images/content/botathon/.
$seasons = array(
    array('n' => 7, 'year' => 2025, 'theme' => 'Keep on Trucking', 'tag' => 'IR-laser combat',
        'blurb' => 'Our biggest season yet &mdash; robots dueled with infrared lasers in last-bot-standing combat.',
        'photos' => array('s7-1.jpg', 's7-2.jpg', 's7-3.jpg')),
    array('n' => 6, 'year' => 2024, 'theme' => 'Capture the Flag', 'tag' => '1v1 block battles',
        'blurb' => 'Robots faced off one-on-one, each side racing to capture the other team&rsquo;s blocks &mdash; blue grabbing red, red grabbing blue.',
        'photos' => array()),
    array('n' => 5, 'year' => 2023, 'theme' => 'Mario Kart', 'tag' => '',
        'blurb' => 'Karts, power-ups and a dash to the finish line &mdash; Botathon went full Mushroom Kingdom.',
        'photos' => array()),
    array('n' => 4, 'year' => 2022, 'theme' => 'Football', 'tag' => '',
        'blurb' => 'A gridiron-inspired season of head-to-head robot action.',
        'photos' => array()),
    array('n' => 3, 'year' => 2021, 'theme' => 'Pirates', 'tag' => '',
        'blurb' => 'Botathon returned from the pandemic with a swashbuckling theme, and we got back to building together.',
        'photos' => array('s3-1.jpg', 's3-2.jpg')),
    array('n' => 2, 'year' => 2020, 'theme' => 'Cancelled', 'tag' => 'COVID-19',
        'blurb' => 'Like most of 2020, Botathon had to take a rain check.',
        'photos' => array(), 'cancelled' => true),
    array('n' => 1, 'year' => 2019, 'theme' => 'Balloons', 'tag' => 'the original',
        'blurb' => 'The very first Botathon &mdash; the one-day build-and-battle that started the whole tradition.',
        'photos' => array()),
);
?>
<style>
    .bpe-lead { max-width: 760px; }
    .bpe-timeline { position: relative; margin: 10px 0 0; padding: 0; list-style: none; }
    /* the green spine */
    .bpe-timeline:before { content: ''; position: absolute; left: 26px; top: 8px; bottom: 8px;
        width: 3px; background: linear-gradient(#00853e, #cfe6d8); border-radius: 2px; }
    .bpe-season { position: relative; padding: 0 0 40px 74px; }
    .bpe-season:last-child { padding-bottom: 6px; }
    .bpe-node { position: absolute; left: 8px; top: 2px; width: 40px; height: 40px; border-radius: 50%;
        background: #00853e; color: #fff; display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 17px; box-shadow: 0 0 0 5px #fff, 0 4px 14px rgba(0,133,62,.35); z-index: 1; }
    .bpe-season.is-cancelled .bpe-node { background: #9ca3af; box-shadow: 0 0 0 5px #fff, 0 4px 10px rgba(0,0,0,.15); }
    .bpe-head { display: flex; align-items: baseline; flex-wrap: wrap; gap: 10px; }
    .bpe-year { font-weight: 800; color: #00853e; font-size: 15px; letter-spacing: .04em; }
    .bpe-season.is-cancelled .bpe-year { color: #9ca3af; }
    .bpe-theme { margin: 2px 0 0; font-size: 24px; line-height: 1.15; }
    .bpe-chip { display: inline-block; background: #eef7f1; color: #00853e; border: 1px solid #cfe6d8;
        font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
        padding: 3px 10px; border-radius: 999px; }
    .bpe-blurb { color: #4b5563; margin: 8px 0 0; max-width: 640px; }
    .bpe-photos { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-top: 16px; max-width: 640px; }
    .bpe-photos img { width: 100%; height: 180px; object-fit: cover; border-radius: 10px; box-shadow: 0 6px 18px rgba(0,0,0,.10); }
    @media (max-width: 575px) {
        .bpe-timeline:before { left: 18px; }
        .bpe-season { padding-left: 58px; }
        .bpe-node { left: 0; width: 36px; height: 36px; font-size: 15px; }
        .bpe-photos img { height: 150px; }
    }
</style>

<main class="page-content">
    <!-- Classic Breadcrumbs-->
    <section class="breadcrumb-classic">
        <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/content/botathon/s7-2.jpg" class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
                <div class="shell">
                    <ul class="list-breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li><a href="/botathon">Botathon</a></li>
                        <li>Past Events</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="section-50">
        <div class="shell">
            <div class="range range-md-justify">
                <div class="cell-md-12 cell-lg-10">
                    <div class="inset-md-right-30 inset-lg-right-0 text-left">
                        <h1>Botathon <strong>Through the Years</strong></h1>
                        <p class="bpe-lead">Every spring, UNT students get one day to design, build and battle a robot from scratch. Each Botathon comes with its own theme, dreamed up by our officers. Here&rsquo;s every season so far &mdash; <?php echo count(array_filter($seasons, function ($s) { return empty($s['cancelled']); })); ?> events and counting.</p>

                        <ul class="bpe-timeline offset-top-30">
                            <?php foreach ($seasons as $s) { $cancel = !empty($s['cancelled']); ?>
                            <li class="bpe-season<?php echo $cancel ? ' is-cancelled' : ''; ?>">
                                <span class="bpe-node"><?php echo (int) $s['n']; ?></span>
                                <div class="bpe-head">
                                    <span class="bpe-year">Season <?php echo (int) $s['n']; ?> &middot; <?php echo (int) $s['year']; ?></span>
                                    <?php if ($s['tag'] !== '') { ?><span class="bpe-chip"><?php echo $s['tag']; ?></span><?php } ?>
                                </div>
                                <h2 class="bpe-theme"><?php echo $cancel ? '<em>Cancelled</em>' : $s['theme']; ?></h2>
                                <p class="bpe-blurb"><?php echo $s['blurb']; ?></p>
                                <?php if (!empty($s['photos'])) { ?>
                                <div class="bpe-photos">
                                    <?php foreach ($s['photos'] as $ph) { ?>
                                    <img src="/images/content/botathon/<?php echo htmlspecialchars($ph); ?>" alt="Botathon Season <?php echo (int) $s['n']; ?> &mdash; <?php echo htmlspecialchars($s['theme']); ?>" loading="lazy">
                                    <?php } ?>
                                </div>
                                <?php } ?>
                            </li>
                            <?php } ?>
                        </ul>

                        <div class="offset-top-30">
                            <a href="/botathon" class="btn btn-default btn-round">Back to Botathon</a>
                            <a href="/botathon/register" class="btn btn-primary btn-round" style="margin-left:8px;">Register for next season</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
footer();
