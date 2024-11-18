<?php
require('template/top.php');
head('Events Calendar', true, false, false, "Stay updated with UNT Robotics' upcoming meetings and events! Discover upcoming workshops, competitions, meetings, and more.");
?>
    <style>
        @media (min-width: 480px) and (max-width: 768px) {
            .col-sm-12 {
                width:100%;
            }
        }
        .calendarFix {
            border: 0;
            width: 100%;
            height: 90vh;
        }

    </style>
    <main class="page-content">
        <section class="section-25 section-md-50 section-lg-75">
            <h1 class="text-center">Events Calendar</h1>
            <div class="text-center">For the most up-to-date information please <a href="/discord">join our Discord</a>.</div>
            <br>
            <div class="shell text-sm-left">
                <div class="range text-center">
                    <div class="col-sm-12">
                        <iframe src="https://calendar.google.com/calendar/embed?src=untroboticsclub%40gmail.com&ctz=America%2FChicago" class="calendarFix" style="border: 0" height="600"></iframe>
                    </div>
                </div>
            </div>
        </section>
    </main>

<?php
footer();
