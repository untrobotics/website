<?php
require('../template/top.php');



global $db;



if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}


$id = $_GET['id'];

//mysqli_select_db($db,"botathon_score");
$query = $db->query("SELECT * FROM botathon_score WHERE id = '". $db->real_escape_string($id) ."'");
//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);

//$dropdown = '<select onchange ="dropDownChanged(this.value)">';

$matchScores = "<div>";
//<input type=\"submit\" value=\"Submit\" style = \"margin-top: 16px;\"><br>
//<form style = \"display: inline-block;\" onchange = \"scoreChanged(" . $id . "," . $row["team_name1"] . ", this.value)\">
while($row = $query->fetch_array(MYSQLI_ASSOC)){
    $matchScores = $matchScores . "<form style = \"display: inline-block;\" onchange = \"scoreChanged(" . $id . ",'" . $row["team_name1"] . "', 1, document.getElementById('score1').value);\">
                    
                    <label for=\"teamName1\">Team 1 score: " . $row["team_name1"] .  " </label><br>
                    <input type=\"number\" id=\"score1\" name=\"score1\" value = \"" . $row['team_score1'] . "\"><br>
                    
    </form>";

    $matchScores = $matchScores . "<form style = \"display: inline-block;\" onchange = \"scoreChanged(" . $id . ",'" . $row["team_name2"] . "', 2, document.getElementById('score2').value);\">
                    
                    <label for=\"teamName2\">Team 2 score: " . $row["team_name2"] .  " </label><br>
                    <input type=\"number\" id=\"score2\" name=\"score2\" value = \"" . $row['team_score2'] . "\"><br>
                    
    </form>";

    $matchScores = $matchScores . "<button onclick = \"startTimer(" . $id .");\"> Start Timer </button>";

}

echo $matchScores . "</div>";

//echo "document.getElementById('modifyingMatch').innerHTML = '<div> " . $matchScores . "</div>';";


?>

