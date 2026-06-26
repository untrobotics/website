<?php
require('../template/top.php');
?>


<!--   mysql> CREATE TABLE botathon_score(id INT AUTO_INCREMENT PRIMARY KEY,team_name1 VARCHAR(50), team_name2 VARCHAR(50), team_score1 INT, team_score2 INT, start_new_timer_bool INT); -->


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
        grid-template-columns: auto auto auto;
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


<body>
<?php

global $db;

$q = intval($_GET['q']);

if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}

//mysqli_select_db($db,"botathon_score");
$query = $db->query("SELECT * FROM botathon_score WHERE id = '". $db->real_escape_string($q) ."'");
//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);

while($row = $query->fetch_array(MYSQLI_ASSOC)) {

    $time = DateTime::createFromFormat ( "Y-m-d H:i:s", $row["start_timestamp"] );
    $current = new dateTime();
    $i = $current->format("i");
    $s = $current->format("s");

    $time->modify("+3 minutes");
    $time->modify("-" . $i . " minutes");
    $time->modify("-" . $s . " seconds");




    echo "<div class=\"grid-container green-box\" style = \"margin-top: 32px; margin-bottom: 32px;\" >";
    echo "<h2 class=\"grid-child green-box\" style = \"width: 100%; height = 1em; border-bottom: none;\">" . $row['team_name1'] . "<div class = \"green-box\" style = \" width: 80px;\">" . $row['team_score1'] . "</div></h2>";
    //code that no work
    //<img src = '/images/bio-pics/temp-pic.jpg' onload = 'countdown( \"timer\", " . $time->format('i') . ", " . $time->format('s') . "); alert('ahhh');'/>
    echo "<h2 class = \"green-box\" style = \"width = 15%; height: 240px; font-size: 2.5em;padding: 14px; border: 1px solid #000;\">Time Left: <div id = \"timer\" class = \"green-box\" style = \"background-color: white;  color: black;\" onclick = \"countdown('timer', " . $time->format('i') . ", " . $time->format('s') . ");\">Click to show timer</div></h2>";
    echo "<h2 class=\"grid-child green-box\" style = \"width: 100%; height = 1em; border-bottom: none;\">" . $row['team_name2'] . "<div class = \"green-box\" style = \" width: 80px;\">" . $row['team_score2'] . "</div> </h2>";
}

?>
</body>