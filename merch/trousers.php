<?php
require('../template/top.php');
require(BASE . '/template/functions/functions.php');
require('includes/merch-template.php');
head('Order Trousers', true);

merch_template("Trousers", "(Trousers)");

footer(false);
?>