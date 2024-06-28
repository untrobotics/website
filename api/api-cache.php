<?php
function getFromApiCacheTable(string $endpoint)
{
    global $db;
    $q = $db->query('SELECT * FROM api_cache WHERE endpoint = "' . $db->real_escape_string($endpoint) . '"');
    if ($q) {
        $r = $q->fetch_array(MYSQLI_ASSOC);
        return $r;
    }
    return null;
}

function getCached(string $endpoint)
{
    global $db;
    $r = getFromApiCacheTable($endpoint);
    if ($r) {
        $config = $db->query('SELECT * FROM outgoing_request_cache_config WHERE id = ' . $r['config_id'])->fetch_array(MYSQLI_ASSOC);
        $ttl = $config['ttl'];
        $now = time();
        $retrievalTime = strtotime($r['last_successfully_retrieved'] . 'UTC');
        if ($now - $retrievalTime <= $ttl) {
            return $r;
        }
        return false;
    }
    return null;
}

function insertCached(string $endpoint, string $content, $config)
{
    global $db;
    $r = getFromApiCacheTable($endpoint);
    $queryString = "";
    if ($r) {
        $id = $r['id'];
        $queryString = 'UPDATE api_cache 
                        SET last_successfully_retrieved = UTC_TIMESTAMP, 
                            last_attempted_retrieval = UTC_TIMESTAMP, 
                            retry_count = 0, 
                            content =\'' . $db->real_escape_string($content) . '\'
                        WHERE id = ' . $id;

    } else {
        $configId = 1;
        if (gettype($config) == 'integer') {
            $configId = $config;
        } else if (gettype($config) == 'string') {
            $configId = $db->query('SELECT * FROM outgoing_request_cache_config WHERE config_name = "' . $db->real_escape_string($config) . '"')->fetch_array(MYSQLI_ASSOC)['id'];
        }
        $queryString = "INSERT INTO api_cache (endpoint, last_successfully_retrieved, last_attempted_retrieval, config_id, content) 
                    values ('" . $db->real_escape_string($endpoint) . "', UTC_TIMESTAMP,
                            UTC_TIMESTAMP," . $configId . ", '" . $db->real_escape_string($content) . "')";
    }
    $q = $db->query($queryString);
    if ($q === false) {
        error_log('Failed to update API cache: ' . $db->error);
    }
}

function clearCached(string $endpoint = null){
    global $db;
    if ($endpoint) {
        $queryString = "DELETE FROM api_cache WHERE endpoint = '" . $db->real_escape_string($endpoint) . "'";
    } else{
        $queryString = "DELETE FROM api_cache";
    }
    $q = $db->query($queryString);
    if ($q === false) {
        error_log("Failed to clear API cache: " . $db->error);
    }
}

function addNewCacheConfig(int $ttl, $config_name = null)
{
    global $db;
    if ($config_name) {
        return $db->query('INSERT INTO outgoing_request_cache_config (ttl, config_name) VALUES (' . $ttl . ', "' . $db->real_escape_string($config_name) . '")');
    }
    return $db->query('INSERT INTO outgoing_request_cache_config (ttl) VALUES (' . $ttl . ')');
}

function updateCacheConfig(int $id, $config_name = null, $ttl = null){
    global $db;
    if($config_name === null){
        if($ttl === null)
            return;
        $queryString = 'UPDATE outgoing_request_cache_config SET ttl = ' . $ttl . ' WHERE id = ' . $id;
    } else if($ttl === null){
        $queryString = "UPDATE outgoing_request_cache_config SET config_name = '{$db->real_escape_string($config_name)}' WHERE id = {$id}";
    } else{
        $queryString = "UPDATE outgoing_request_cache_config SET ttl= {$ttl}, config_name = '{$db->real_escape_string($config_name)}' WHERE id = {$id}";
    }
    $q = $db->query($queryString);
    if(!$q){
        error_log('Failed to update API cache config: ' . $db->error);
    }
}