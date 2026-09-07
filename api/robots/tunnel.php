<?php
require_once('../../template/top.php');
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"));

// Validate the body before reading it. Bots/scanners hit this endpoint with empty
// or non-JSON bodies, which used to throw "read property on null" warnings (they
// were flooding the web-logs channel). Reject anything without the required fields.
if (!is_object($data) || !isset($data->endpoint, $data->tunnel, $data->api_key)) {
    http_response_code(400);
    echo json_encode(array("response" => "Invalid request."));
    exit;
}

$endpoint = $data->endpoint;
$tunnel_url = $data->tunnel;
$api_key = $data->api_key;

$status_dir = 'tunnel-status';

$a = array();

$q = $db->query('SELECT endpoint, internal_port FROM tunnel_api_keys WHERE api_key_value = "' . $db->real_escape_string($api_key) . '"');
if ($q->num_rows > 0) {
	// valid
	$r = $q->fetch_array(MYSQLI_ASSOC);
	if ($r['endpoint'] === $endpoint) {
		$tunnel_parts = parse_url($tunnel_url);
		$tunnel_host = $tunnel_parts['host'];
		$tunnel_port = $tunnel_parts['port'];
		$tunnel_ip = gethostbyname($tunnel_host);
		$internal_port = intval($r['internal_port']);

		// tunnel status computed

		$status = "{$tunnel_ip}|{$tunnel_port}|{$internal_port}";

		if (!is_writeable($status_dir)) {
			$a = array("response"=>"Server error, cannot write status.");
		} else {
			$status_file = $status_dir . '/' . $endpoint;
			if (!is_file($status_file)) {
				file_put_contents($status_file, $status);
			}

			$stored_status = file_get_contents($status_file);

			if ($status !== $stored_status) {
				// update IP tables

				// first remove the old rules
				file_put_contents($status_file, '|DELETE', FILE_APPEND); // triggers incron update

				sleep(0.5);

				// add the new rules
				file_put_contents($status_file, $status); // triggers incron update
			}
			
			$a = array("response"=>"Successfully updated tunnel.");
		}
	} else {
		$a = array("response"=>"Endpoint does not match API key.");
	}
} else {
	$a = array("response"=>"Invalid API key.");
}

echo json_encode($a);
?>