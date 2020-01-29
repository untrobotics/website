<?php
require('template/top.php');
head('Events Calendar', true);
?>

<main class="page-content">
        <section class="section-25 section-md-50 section-lg-75">
			<h1 class="text-center">Events Calendar</h1>
			<div class="text-center">For the most up-to-date information please <a href="/join/discord">join our Discord</a>.</div>
			<br>
                <div class="shell text-sm-left">
                        <div class="range text-center">
                                <div class="col-lg-12">
					<iframe src="https://calendar.google.com/calendar/embed?src=untroboticsclub%40gmail.com&ctz=America%2FChicago" style="border: 0" width="800" height="600" frameborder="0" scrolling="no"></iframe>
                                </div>
                        </div>
                </div>
        </section>
</main>

<?php
footer();
