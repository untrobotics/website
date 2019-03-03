<?php
require("config.php");
require("namecom.php");
$api = new NameComApi();
$api->login(NAMECOM_API_USERNAME, NAMECOM_API_KEY);

$sub_domain = $_GET['sub_domain'];
$super_domain = $_GET['super_domain'];

$a = array();

$auth_api = false;
if (isset($_GET['API_KEY'])) {
	if (in_array($_GET['API_KEY'], $api_keys)) {
		$auth_api = true;
	}
}

if ($auth_api) {
	if (!isset($super_domain)) {
		$a = array("response"=>"You must specify a super domain.");
	} else {
		$ip = ((@$_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR']);
		$ttl = ((@$_GET['ttl']) ? $_GET['ttl'] : 300); // default 5 minute time to live

		$response = $api->get_domain($super_domain);

		if ($response->result->code != 100) {
		        $a = array("response"=>"You probably don't own the domain.","code"=>$response->result->code);
		} else {
			$response = $api->list_dns_records($super_domain);

			if ($response->result->code != 100) {
			        $a = array("response"=>"An unknown error has occured when querying the superdomain.","code"=>$response->result->code);
			} else {
				foreach ($response->records as $key => $val) {
				        if ($val->name == $sub_domain.".".$super_domain) { // delete entry
				                $_response = $api->delete_dns_record($super_domain, $val->record_id);
				                if ($_response->result->code != 100) {
				                        $a = array("response"=>"An unknown error has occured when deleting the old entry.","code"=>$_response->result->code);
							break;
				                }
				        }
				}

				if (empty($a)) {
					if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
					        // $ip is valid IPv6
					        $record_type = "AAAA";
					} else {
					        $record_type = "A";
					}

					$response = $api->create_dns_record($super_domain, $sub_domain, $record_type, $ip, $ttl);

					if ($response->result->code != 100) {
					        $a = array("response"=>"An unknown error has occured when updating the record.","code"=>$response->result->code);
					} else {
					        $a = array("response"=>"IP updated successfully.","code"=>$response->result->code);
					}
				}
			}
		}
	}
} else {
	$a = array("response"=>"Invalid API key.");
}

echo json_encode($a);

$api->logout();
?>
