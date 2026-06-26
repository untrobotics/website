<?php
require("../template/top.php");
require("../api/discord/bots/admin.php");
$oos_message = "CurrentlySoldOut";
$in_stock_filter = '<span class="FloatLeft" style="display: ;">';

$url = "https://www.perfectflitedirect.com/stratologgercf-altimeter/";
$f = file_get_contents($url);
if (!preg_match("/{$oos_message}/", $f) && preg_match("/{$in_stock_filter}/", $f)) {
    AdminBot::send_message("Stratologger CF is in stock NOW at {$url} (**<@877618983401037854>**)", 834922880415432754);
}