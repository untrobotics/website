<?php
require("../template/top.php");

$u = @$_GET['u'];
if (empty($u)) {
    die("UID not specified.");
}

if (!is_numeric($u)) {
    die("Invalid UID provided.");
}

if ($untrobotics->is_user_in_good_standing($u)) {
    echo "User #$u is in good standing. :)";
} else {
    echo "User #$u is <strong>NOT</strong> in good standing. :(";
}