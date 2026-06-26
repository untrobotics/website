<?php

$device_id = $_GET['name'];

$f = file_get_contents("locations/$device_id");
$json = json_decode($f);

$lat = $json->latitude;
$lon = $json->longitude;

?>
<a href="https://maps.google.com/?q=<?php echo $lat; ?>,<?php echo $lon; ?>"><?php echo $lat; ?>, <?php echo $lon; ?></a>
