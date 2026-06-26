<?php
require('../template/top.php');


global $db;

//echo print_r($_GET);

if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}

$id = $_GET['id'];

//mysqli_select_db($db,"botathon_score");
$query = $db->query("ORDER BY id DESC LIMIT 1");
//TODO unset current match for all, set currentMatch for id

//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);


//echo "UPDATE botathon_score SET start_timestamp = NOW() WHERE id =' " .  $db->real_escape_string($id) . "'";


echo $query['id'];


//echo ;

?>
