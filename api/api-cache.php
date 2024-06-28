<?php
// these functions use $db, so scripts referencing them need to define it

/**
 * Gets the first entry from the API cache that has a matching endpoint.
 * @param string $endpoint The endpoint for the API request
 * @return array|null The first entry that matches or null if no entry exists
 */
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

/**
 * Gets the first entry from the API cache that has a matching endpoint. Will not return if the entry is expired.
 * @param string $endpoint The endpoint for the API request
 * @return array|false|null The first entry that matches, false if the entry is expired, or null if no entry exists
 */
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

/**
 * Adds or updates an entry into the cache table.
 * @param string $endpoint The endpoint for the API request
 * @param string $content The content of API request's response
 * @param int|string $config The ID of the config or its name in the config table
 */
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

/**
 * Deletes an entry from the cache or deletes all entries from the cache.
 * @param string|null $endpoint The endpoint of the API request to be removed. If null, empties the cache
 */
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

/**
 * Adds a new cache config to the config table.
 * @param int $ttl The time to live for cached entries, measured in seconds
 * @param string|null $config_name (optional) The name of the config
 * @return bool|mysqli_result The results of the MySQLi query
 */
function addNewCacheConfig(int $ttl, $config_name = null)
{
    global $db;
    if ($config_name) {
        return $db->query('INSERT INTO outgoing_request_cache_config (ttl, config_name) VALUES (' . $ttl . ', "' . $db->real_escape_string($config_name) . '")');
    }
    return $db->query('INSERT INTO outgoing_request_cache_config (ttl) VALUES (' . $ttl . ')');
}

/**
 * Updates the information of one of the cache configs. Does nothing if both $config_name and $ttl are null
 * @param int $id The ID of the config to be updated
 * @param string|null $config_name (optional) The name of the config
 * @param int|null $ttl (optional) The time to live for cached entries, measured in seconds
 */
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