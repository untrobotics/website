<?php
require('../template/top.php');
require(BASE . '/template/functions/functions.php');
require('includes/merch-template.php');
head('Order Shirts & Hoodies', true);

merch_template("Shirts & Hoodies");

footer(false);
?>
