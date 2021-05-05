<?php

if (@$_SERVER['HTTPS'] === "on") {
    $location = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header('HTTP/1.1 301 Moved Permanently');
    header('Location: ' . $location);
    exit;
}

require('../template/top.php');

$head = head('Botathon Live Page', true, true, true);

//get team name and number
global $db;
global $userinfo;

$team_number = $_GET['team_num'];

$id = $userinfo["id"];
$q = $db->query("SELECT * FROM `botathon_team_member` WHERE
                                           `uid` = '" . $db->real_escape_string($id) . "' AND
                                           `team_num` = '" . $db->real_escape_string($team_number) . "'
                                           ");
if ($q->num_rows > 0) {
    $q = $db->query("SELECT * FROM `botathon_teams` WHERE `team_num` = '" . $db->real_escape_string($team_number) . "'");
    $r = $q->fetch_array(MYSQLI_ASSOC);

    $name = $r['team_name'];
    $secret = $r['secret_key'];
} else {
    die("Access Denied");
}

echo $head;

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

    .stream{
        padding: 10px 20px 10px 20px;
        display: grid;
        grid-template-columns: auto 20px auto;
    }

    .grid-item {
        padding: 0px;
        text-align: center;
        width: 100%;
    }

    .websocket-feed{
        margin-left: 20%;
        background-color: #aaa;
        color: black;
        width: 50%;
        text-align: center;
        height: 400px;
        overflow: scroll
    }
</style>

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

    <section class="section-50" id="sponsors">
    <div style="margin: 20px">
        <h1><?php echo $name; ?> team page</h1>
        <h3>Team number: <?php echo $team_number; ?></h3>

        <!--
        <div class="stream">
            <div class="grid-item">
                <iframe src="http://player.twitch.tv/?channel=untroboticsevents&parent=untrobotics.com" frameborder="0" allowfullscreen="true" scrolling="no" height="378" width="620"></iframe>
            </div>
            <div>
            </div>
            <div class="grid-item">
                <iframe src="http://player.twitch.tv/?channel=untroboticsevents2&parent=untrobotics.com" frameborder="0" allowfullscreen="true" scrolling="no" height="378" width="620"></iframe>
            </div>
        </div>
        -->

            <h2>Driver control</h2>
            <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
            <h1 >Press keys...</h1>
            WebSocket status : <span id="message"></span>

            <div class = "websocket-feed">
                <ul id = "demo">

                </ul>
            </div>

    </div>
    </section>
</main>

<?php
footer(false);
?>
<script src="/js/reconnecting_websocket.js"></script>
<script>
    //script
    var ws = new ReconnectingWebSocket("ws://untrobotics.com:9111", "team");
    ws.debug = true;
    //var ws = new WebSocket("ws://untrobotics.com:9111", "team");
    ws.onopen = function () {

        <?php
        $validate = new stdClass();
        $validate->TEAM_NO = "TEAM_NO_" . (string)(100 + intval($team_number));
        $validate->SECRET_KEY = $secret;

        $validate_string = json_encode($validate);
        ?>

        const validate_string = '<?php echo $validate_string?>';

        ws.send(validate_string);

        $('#message').text('connected');
        $("#demo").prepend(`<li>Socket connected successfully.</li>`);
    };
    ws.onmessage = function (ev) {
        $('#message').text('recieved message');
    };

    ws.onclose = function (ev) {
        $('#message').text('WebSocket has closed');
    };
    ws.onerror = function (ev) {
        $('#message').text('error occurred');
    };

    // TODO current bug - won't send up for first key pressed
    // so just padding the array with a dummy value so slice works

    const keyIntervals = {};

    $(document.body).keydown(function (evt) {
        const keyCode = evt.which;

        if (keyCode === 18 || keyCode === 17) {
            return;
        }

        if (!keyIntervals[keyCode]) {
            ws.send(keyCode);
            $("#demo").prepend(`<li>Key Pressed: ${keyCode} (${evt.key})</li>`);

            keyIntervals[keyCode] = setInterval(function () {
                ws.send(keyCode);
                $("#demo").prepend(`<li>Key Pressed: ${keyCode} (${evt.key})</li>`);
            }, 150);
        }
    });
    $(document.body).keyup(function (evt) {
        const keyCode = evt.which;
        $("#demo").prepend(`<li>Key UP: ${keyCode} (${evt.key})</li>`);
        clearInterval(keyIntervals[keyCode]);
        delete keyIntervals[keyCode];
    });
</script>

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
    });
</script>
