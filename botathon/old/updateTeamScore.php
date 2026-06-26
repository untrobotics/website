<?php
require('../template/top.php');


global $db;

//echo print_r($_GET);

$id = $_GET["id"];
$score = $_GET["newScore"];
$teamNum = $_GET["teamNum"];



if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}

//mysqli_select_db($db,"botathon_score");
$query = $db->query("UPDATE botathon_score SET team_score". $db->real_escape_string($teamNum) . "=". $db->real_escape_string($score) . " WHERE id='" .  $db->real_escape_string($id) . "'");
//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);


echo "UPDATE botathon_score SET team_score". $db->real_escape_string($teamNum) . "=". $db->real_escape_string($score) . " WHERE id='" .  $db->real_escape_string($id) . "'";



//echo ;

?>
