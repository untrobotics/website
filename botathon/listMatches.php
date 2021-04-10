<?php
//require('../template/top.php');



global $db;

if (!$db) {
    die('Could not connect: ' . mysqli_error($db));
}

//mysqli_select_db($db,"botathon_score");
$query = $db->query("SELECT * FROM botathon_score");
//$sql="SELECT * FROM user WHERE id = '".$q."'";
//$result = mysqli_query($db,$sql);

$dropdown = '<select id = "matchDropDown" onchange ="dropDownChanged(this.value)">';

while($row = $query->fetch_array(MYSQLI_ASSOC)){
    $dropdown = $dropdown . "<option value=" . $row["id"] . ">" .  $row["team_name1"] . " and " .  $row["team_name2"] . "</option>";
}

$dropdown = $dropdown . '</select>';

echo $dropdown;

?>
