<?php

function botathon_spots_remaining() {
	global $db;
	$q = $db->query('SELECT count(id) FROM botathon_registration');
	$r = $q->fetch_array(MYSQLI_NUM);

	return (BOTATHON_REGISTRATION_LIMIT - $r[0]);
}