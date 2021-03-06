<?php
require('../template/top.php');
require(BASE . '/template/functions/functions.php');
require('includes/merch-template.php');
head('Order Gear', true);

merch_template("Gear", "(Gear)");

footer(false);
?>