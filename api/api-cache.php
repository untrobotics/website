<?php
require('../template/top.php');
class ApiCache
{
    private static function get(string $endpoint){
        $q = $db->query('SELECT * FROM api_cache WHERE endpoint = "' . $db->real_escape_string($endpoint) . '"');
        if ($q) {
            $r = $q->fetch_array(MYSQLI_ASSOC);
            return $r;
        }
        return null;
    }

    public static function getCached(string $endpoint){
        $r = self::get($endpoint);
        if ($r) {
            $config = $db->query('SELECT * FROM outgoing_request_cache_config WHERE id = '. $r['config_id']);
            $ttl = $config['ttl'];
            $now = time();
            if($now - $r['last_successfully_retrieved'] <= $ttl){
                return $r;
            }
        }
        return null;
    }

    public static function put(string $endpoint, string $content, $config){
        $r = self::get($endpoint);
        $queryString = "";
        if($r) {
            $id = $r['id'];
            $queryString = 'UPDATE api_cache SET last_successfully_retrieved = UTC_TIMESTAMP, last_attempted_retrieval = UTC_TIMESTAMP, retry_count = 0, content =' . $db->real_escape_string($content) . 'WHERE id = . $id)';

        } else{
            $configId = 1;
            if(gettype($config) == 'integer'){
                $configId = $config;
            } else if (gettype($config) == 'string'){
                $configId = $db->query('SELECT * FROM outgoing_request_cache_config WHERE config_name = "' . $db->real_escape_string($config) . '"')->fetch_array(MYSQLI_ASSOC)['id'];
            }
            $queryString = "INSERT INTO api_cache (endpoint, last_successfully_retrieved, last_atetempted_retrieval, config_id, content) 
                    values (" . $db->real_escape_string($endpoint) . ", UTC_TIMESTAMP,
                            UTC_TIMESTAMP," . $configId .  ", '" . $db->real_escape_string($content) . "',)";
        }
        $q = $db->query($queryString);
        if($q === false){
            error_log('Failed to update API cache: ' . $db->error);
        }
    }
}