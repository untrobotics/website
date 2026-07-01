<?php
require("../template/top.php");

// Admin-only: exposes any member's standing by UID (membership enumeration).
if (!is_array(auth(2))) {
    header("Location: /auth/login?returnto=" . $_SERVER['REQUEST_URI']);
    die();
}

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