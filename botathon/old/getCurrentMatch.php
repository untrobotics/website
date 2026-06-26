<?php
require('../template/top.php');


global $db;

//echo print_r($_GET);

if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}

//mysqli_select_db($db,"botathon_score");
//ORDER BY id DESC LIMIT 1
//$query = $db->query("SELECT * FROM botathon_score WHERE id = '". $db->real_escape_string($q) ."'");
//$query = $db->query("ORDER BY id DESC LIMIT 1");
$query = $db->query("SELECT * FROM botathon_score ORDER BY id DESC LIMIT 1");

//TODO unset current match for all, set currentMatch for id

//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);


//echo "UPDATE botathon_score SET start_timestamp = NOW() WHERE id =' " .  $db->real_escape_string($id) . "'";


while($row = $query->fetch_array(MYSQLI_ASSOC)) {
    echo $row['id'];
}



//echo ;

?>
