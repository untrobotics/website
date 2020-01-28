<?php

require('../../template/top.php');

$q = $db->query("SELECT * FROM dues_payments");

echo '<pre>';

while ($r = $q->fetch_array(MYSQLI_ASSOC)) {
	echo $r['name'] . ' -> ' . $r['email'] . PHP_EOL;
}

echo '</pre>';