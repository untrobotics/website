<?php
require('../template/top.php');
require(BASE . '/template/functions/functions.php');
require('includes/merch-template.php');
head('Order Gear', true, false, false, "Support UNT Robotics & look dapper while doing it!");

merch_template("Gear", "(Gear)");

footer(false);
?>