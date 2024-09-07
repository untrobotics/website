<?php
if($_SERVER["REQUEST_METHOD"] == "POST"){
    $request = json_decode(file_get_contents("php://input"));

}

require_once('../template/top.php');
