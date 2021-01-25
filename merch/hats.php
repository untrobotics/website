<?php
require('../template/top.php');
require(BASE . '/template/functions/functions.php');
require('includes/merch-template.php');
head('Order Hats', true);

merch_template("Hats", "(Hat)");

footer(false);
?>