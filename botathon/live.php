<?php
require('../template/top.php');
head('Botathon Live Page', true);
?>

<style>
    h2 {
        border-bottom: 1px solid lightgray;
    }
    #schedule tr td:first-of-type {
        font-weight: 800;
        padding-right: 15px;
        font-family: Consolas, "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", Monaco, "Courier New", "monospace";
        color: black;
    }

    #partsTable1{
        width: 70%;
    }

    .parts tr {
        border-bottom: 1px solid gray;
    }
    .parts tr th {
        color: black;
    }
    .parts tr td:first-of-type {
        width: 150px;
        font-weight: 800;
    }
    .parts tr td:nth-of-type(2) {
        width: 150px;
    }
    .parts.basekit tr td:nth-of-type(3) {
        width: 250px;
    }
    .parts.upgrades tr td:nth-of-type(3) {
        width: 150px;
    }

    .field-preview h6 > a {
        height: 100px;
        display: block;
    }

    table#schedule tr td:first-of-type {
        min-width: 100px;
    }

    .page #botathon-navigation ul li.active a {
        color: #45cd8f !important;
    }

    .page #botathon-navigation ul li a,
    .page #botathon-navigation ul li
    {
        transition: all 0.5s linear;
    }

    .sponsors {
        text-align: center;
    }
    .sponsors .sponsor {
        display: inline-block;
        margin: 0 20px;
        padding: 10px;
    }

    @media (min-width: 992px) {
        #botathon-navigation {
            position: sticky;
            position: -webkit-sticky;
            top: 0;
            /* height: 0px; */
            float: right;
            /* right: -500px; */
            width: 300px;
            /* padding: 0px; */
            margin-right: 50px;
        }
        .botathon-navigation {
            padding-top: 50px;
        }
    }

    .grid-container {
        margin-top: 40px;
        margin-bottom: 80px;
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        width: 800px;
        border: 4px solid #000;
        border-radius: 25px;
    }

    .greyOutline{
        outline: 2px dashed blue;
    }

    .stream{
        display: grid;
        grid-template-columns: 48% 48%;
    }

    .grid-item {
        padding: 10px;
        text-align: center;
        width: 100%;
    }

    .grid-child{
        height: 1.5em;
    }

    .green-box{
        text-transform: uppercase;
        text-align: center;
        padding: 8px;
        margin: 4px auto;
        border-spacing: 8px;
        background-color: #3e7553;
        color: #ffffff;
    }

    #timer{
        font-size: 1.2em font-weight: bolder

    }

    .title{
        text-align: center;
        margin-top: 30px;
    }

</style>

<script>
    //TODO change to AJAX request so that can be updated remotely
    function countdown(element, minutes, seconds) {
        console.log(minutes, seconds);
        // set time for the particular countdown
        var time = minutes*60 + seconds;
        var interval = setInterval(function() {
            var el = document.getElementById(element);
            // if the time is 0 then end the counter
            if(time == 0) {
                el.innerHTML = "Move on to next date...";
                clearInterval(interval);
                setTimeout(function() {
                    countdown('clock', 3, 0);
                }, 2000);
            }
            var minutes = Math.floor( time / 60 );
            if (minutes < 10) minutes = "0" + minutes;
            var seconds = time % 60;
            if (seconds < 10) seconds = "0" + seconds;
            var text = minutes + ':' + seconds;
            el.innerHTML = text;
            time--;
        }, 1000);
    }
    //Call to start timer
    //countdown("timer", 3, 0);


    //AJAX
    function showScoreBoard(id) {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("scoreBoard").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET",`getScore.php?q=${id}`,true);
        xmlhttp.send();
    }

    //AJAX
    function getCurrentMatch() {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                //document.getElementById("test").innerHTML = this.responseText;
                return this.responseText;
            }
        };
        xmlhttp.open("GET",`getCurrentMatch.php`,true);
        xmlhttp.send();
    }

    //pull most recent match
    function showScoreBoardRecent() {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("scoreBoard").innerHTML = this.responseText;
            }
        };
        xmlhttp.open("GET",`getScoreRecent.php?`,true);
        xmlhttp.send();
    }



</script>

<main class="page-content">
    <!-- Classic Breadcrumbs-->
    <section class="breadcrumb-classic">
        <div class="rd-parallax">
            <div data-speed="0.25" data-type="media" data-url="/images/hackathon-demo.jpg" class="rd-parallax-layer"></div>
            <div data-speed="0" data-type="html" class="rd-parallax-layer section-top-75 section-md-top-150 section-lg-top-260">
                <div class="shell">
                    <ul class="list-breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li>Botathon</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!--<section class="section-50">
        <div class="shell">
            <div class="range range-md-justify">
                <div class="cell-lg-12">
                    <center><a href="register" class="btn btn-default btn-round" style="margin: 20px;">Register Now</a></center>
                    <iframe src="https://www.untrobotics.com/pdfjs/web/viewer.html?file=/downloads/botathon-info-packet.pdf" width="100%" height="1000"></iframe>
                </div>
            </div>
        </div>
    </section>-->

    <h4 class = "title" style="margin-bottom: 20px;">Please join our Discord to participate in Botathon!</h4>

</main>
<?php
footer(false);
?>
<script>
    $("#botathon-navigation ul li a").click(function(e) {
        $("#botathon-navigation ul li.active").removeClass("active");
        $(this).parent().addClass("active");
    });

    $(document).ready(function(){
        // Add smooth scrolling to all links
        $("#botathon-navigation ul li a").on('click', function(event) {

            // Make sure this.hash has a value before overriding default behavior
            if (this.hash !== "") {
                // Prevent default anchor click behavior
                event.preventDefault();

                // Store hash
                var hash = this.hash;

                // Using jQuery's animate() method to add smooth page scroll
                // The optional number (800) specifies the number of milliseconds it takes to scroll to the specified area
                $('html, body').animate({
                    scrollTop: $(hash).offset().top
                }, 800, function(){

                    // Add hash (#) to URL when done scrolling (default click behavior)
                    window.location.hash = hash;
                });
            } // End if
        });

        //AJAX

    });
</script>