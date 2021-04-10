<?php
require('../template/top.php');


global $db;


$team1 = $_POST["teamName1"];
$team2 = $_POST["teamName2"];

if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}

//mysqli_select_db($db,"botathon_score");
$query = $db->query('
                      INSERT INTO botathon_score (team_name1, team_name2, team_score1, team_score2, start_timestamp)
                        VALUES
                        (
                            "' . $db->real_escape_string($team1) . '",
                            "' . $db->real_escape_string($team2) . '",
                            "' . $db->real_escape_string(0) . '",
                            "' . $db->real_escape_string(0) . '",
                            "' . $db->real_escape_string(date("Y-m-d H:i:s")) . '"                        
                        )
                        ');
//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);



//echo ;

?>
