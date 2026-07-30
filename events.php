<?php
require('template/top.php');
head('Events Calendar', true);
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
        <section class="breadcrumb-classic">
          <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/headers/events.jpg" class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
              <div class="shell">
                <ul class="list-breadcrumb">
                  <li><a href="/">Home</a></li>
                  <li>Events</li>
                </ul>
              </div>
            </div>
          </div>
        </section>
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
