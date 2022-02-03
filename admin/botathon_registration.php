<?php

require('../template/top.php');

$q = $db->query("SELECT * FROM botathon_registration");

echo '<pre>';

while ($r = $q->fetch_array(MYSQLI_ASSOC)) {
	//var_dump($r);
	echo '=====================================' . PHP_EOL;
	echo '<strong>' . $r['name'] . '</strong><br>' .
		'EMAIL: ' . $r['email'] . PHP_EOL .
		'PHONE: ' . $r['phone'] . PHP_EOL .
		'GENDER: ' . $r['gender'] . PHP_EOL .
		'CLASSIFICATION: ' . $r['classification'] . PHP_EOL .
		'MAJOR: ' . $r['major'] . PHP_EOL .
		'DIET RESTRICTIONS: ' . $r['diet_restrictions'] . PHP_EOL .
		'LATEX ALLERGY: ' . (($r['latex_allergy'] == 0) ? 'No.' : '<strong>Yes.</strong>') . PHP_EOL .
        'TEAM: ' . $r['team_name'] . PHP_EOL .
        'Disability Accommodations: ' . $r ['disability_accommodations'] . PHP_EOL .
		'EUID: ' . $r['unteuid'] . PHP_EOL;
}

echo '</pre>';
