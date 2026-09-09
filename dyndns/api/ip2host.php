<?php
/**
 * Dynamic-DNS update endpoint. A member's client (router / cron / ddclient-style
 * script) hits this URL with their dyndns API key and the sub-domain to point at
 * their current IP; we upsert the matching A/AAAA record in the Name.com zone.
 *
 *   /dyndns/api/ip2host.php?API_KEY=...&super_domain=untrobotics.com
 *                          &sub_domain=foo.dyndns[&ip=1.2.3.4][&ttl=300]
 *
 * Guards: the API key must exist in `dyndns_api_keys` (optionally restricted to a
 * set of sub-domains), the sub-domain must end in DYNDNS_FORCE_SUBDOMAIN, and the
 * super-domain must be in DYNDNS_ALLOWED_SUPERDOMAINS. If `ip` is omitted the
 * caller's REMOTE_ADDR is used. Output is a small JSON status object.
 */
require_once("../../template/top.php");
require_once("namecom.php");

$api = new NameComApi();
$api->login(NAMECOM_API_USERNAME, NAMECOM_API_KEY);

$sub_domain   = isset($_GET['sub_domain'])   ? $_GET['sub_domain']   : null;
$super_domain = isset($_GET['super_domain']) ? $_GET['super_domain'] : null;

$a = array();

// --- authenticate the caller's dyndns API key against our own DB -------------
$auth_api = false;
if (!empty($_GET['API_KEY'])) {
    $api_key = $_GET['API_KEY'];
    $st = $db->prepare('SELECT subdomain_restrictions FROM dyndns_api_keys WHERE api_key_value = ?');
    $st->bind_param('s', $api_key);
    if ($st->execute() && ($res = $st->get_result()) && $res->num_rows > 0) {
        $r = $res->fetch_assoc();
        // subdomain_restrictions is admin-written (trusted); disallow object
        // instantiation anyway. empty/false = unrestricted.
        $subdomain_restrictions = @unserialize((string) $r['subdomain_restrictions'], ['allowed_classes' => false]);
        if (!$subdomain_restrictions || in_array($sub_domain, $subdomain_restrictions, true)) {
            $auth_api = true;
        }
    }
}

if (!$auth_api) {
    $a = array("response" => "Invalid API key.");
} elseif (!isset($super_domain)) {
    $a = array("response" => "You must specify a super domain.");
} elseif (!(DYNDNS_FORCE_SUBDOMAIN && preg_match('/\.' . DYNDNS_FORCE_SUBDOMAIN . '$/i', (string) $sub_domain))) {
    $a = array("response" => "You are not allowed to modify this subdomain.");
} elseif (!in_array(strtolower($super_domain), DYNDNS_ALLOWED_SUPERDOMAINS)) {
    $a = array("response" => "You are not allowed to modify this superdomain.");
} else {
    $ip  = !empty($_GET['ip'])  ? $_GET['ip']         : $_SERVER['REMOTE_ADDR'];
    $ttl = !empty($_GET['ttl']) ? (int) $_GET['ttl']  : 300; // default 5 minute TTL

    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
        $a = array("response" => "Invalid IP address.");
    } elseif (!$api->owns_domain($super_domain)) {
        $a = array("response" => "You probably don't own the domain.", "code" => $api->last_status);
    } else {
        $records = $api->list_records($super_domain);
        if ($records === null) {
            $a = array("response" => "An unknown error has occured when querying the superdomain.", "code" => $api->last_status);
        } else {
            // Clear any existing records for this host (any type) so the new one
            // is authoritative and A<->AAAA switches don't leave a stale record.
            $delete_failed = false;
            foreach ($records as $rec) {
                if (isset($rec->host) && strcasecmp($rec->host, $sub_domain) === 0) {
                    if (!$api->delete_record($super_domain, $rec->id)) {
                        $a = array("response" => "An unknown error has occured when deleting the old entry.", "code" => $api->last_status);
                        $delete_failed = true;
                        break;
                    }
                }
            }

            if (!$delete_failed) {
                $record_type = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false ? "AAAA" : "A";
                if ($api->create_record($super_domain, $sub_domain, $record_type, $ip, $ttl)) {
                    $a = array("response" => "IP updated successfully.", "code" => $api->last_status);
                } else {
                    $a = array("response" => "An unknown error has occured when updating the record.", "code" => $api->last_status);
                }
            }
        }
    }
}

echo json_encode($a);
