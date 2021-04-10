<?php
require('../template/top.php');


global $db;

//echo print_r($_GET);


if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}

//mysqli_select_db($db,"botathon_score");
$query = $db->query('ORDER BY id DESC LIMIT 1');
//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);


echo $query.value;



//echo ;

?>