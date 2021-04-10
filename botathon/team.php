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

<!-- Core scripts -->
<script src="/assets/vendor/js/pace.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>

<!-- get the team name and number-->
<script>
    <?php
    //get team name and number
    global $db;
    global $userinfo;

    $id = $userinfo["id"];
    $q = $db->query("SELECT * FROM `botathon_team_member` WHERE `uid` = $id;");
    if ($q != false){
        $r = $q->fetch_array(MYSQLI_ASSOC);
        //TEAM NUM
        $num = $r['team_num'];


        $q = $db->query("SELECT * FROM `botathon_teams` WHERE `team_num` = $num;");
        $r = $q->fetch_array(MYSQLI_ASSOC);

        //TEAM NAME
        $name = $r['team_name'];
        $secret = $r['secret_key'];
    } else {

        $name = "error";
        $secret = "error";
        $num = -1;

    }
    ?>
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

    <div class = "inset-md-right-30 inset-lg-right-0">
        <h1 style = "margin: 20px"><?php echo $name; ?> stream page</h1>
        <h3 style = "margin: 20px">Team number: <?php echo $num; ?></h3>

        <div class="stream">
            <div class="grid-item"><iframe width="100%" height="380px" src="https://www.youtube.com/embed/21X5lGlDOfg" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
            <div></div>
            <div class="grid-item"><iframe width="100%" height="380px" src="https://www.youtube.com/embed/21X5lGlDOfg" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>
        </div>

        <h2>Driver control</h2>
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
        <h1>Press keys...</h1>
        WebSocket status : <span id="message"></span>

        <div class = "websocket-feed">
            <ul id = "demo">

            </ul>
        </div>

    </div>
</main>

<?php
footer(false);
?>

<script src="/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
<script src="/assets/vendor/libs/moment/moment.js"></script>
<script src="/assets/vendor/libs/bootstrap-daterangepicker/bootstrap-daterangepicker.js"></script>
<script src="/assets/vendor/libs/datatables/datatables.js"></script>
<script src="assets/js/demo.js"></script>
<script src="/assets/js/reconnecting_websocket.js"></script>


<script>
    //script
    //window.websocket = new ReconnectingWebSocket("wss://" + window.location.hostname + ":9111", "team");
        var ws = new WebSocket("wss://" + window.location.hostname + ":9111", "team");
        ws.onopen = function () {

            <?php
            $validate = new stdClass();
            $validate->TEAM_NO = "TEAM_NO_" . (string)(100 + intval($num));
            $validate->SECRET_KEY = $secret;

            $validate_string = json_encode($validate);
            ?>

            const validate_string = <?php echo $validate_string?>;

            ws.send(validate_string);

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
        var pressedKeys = [999999,];
        $(document.body).keydown(function (evt) {
            const keyCode = evt.keyCode;
            if (pressedKeys.indexOf(evt.keyCode) == -1) {
                pressedKeys.push(evt.keyCode);
                //document.getElementById("demo").innerHTML = "down " + evt.keyCode;

                var list = document.getElementById('demo');
                var entry = document.createElement('li');
                entry.appendChild(document.createTextNode("down " + String.fromCharCode(evt.keyCode)));
                list.insertBefore(entry, list.firstChild);

                ws.send(evt.keyCode + ':d');
            }
        });

        $(document.body).keyup(function (evt) {
            const keyCode = evt.keyCode;
            if (pressedKeys.indexOf(evt.keyCode)) {
                var index = pressedKeys.indexOf(evt.keyCode);
                pressedKeys.splice(index, 1);
                //document.getElementById("demo").innerHTML = "down " + evt.keyCode;
                console.log("up " + evt.keyCode);
                ws.send(evt.keyCode + ':u');
            }

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
