<?php
require_once('../template/top.php');

// RegEx to match certain endpoints we want to ignore, e.g., orders
const IGNORED_ENDPOINTS = '/(?:orders)/i';
/**
 * Gets the first entry from the API cache that has a matching endpoint.
 * @param string $endpoint The endpoint for the API request
 * @return array|null The first entry that matches or null if no entry exists
 */
function get_cached_api_response(string $endpoint): ?array
{
    global $db;
    $q = $db->query('SELECT * FROM api_cache WHERE endpoint = "' . $db->real_escape_string($endpoint) . '"');
    if ($q) {
        $r = $q->fetch_array(MYSQLI_ASSOC);
        return $r;
    }
    return null;
}

class CacheResult{
    /**
     * @var string $content The response content
     */
    public $content;
    /**
     * @var bool $curl_executed true if curl_exec was called, false otherwise
     */
    public $curl_executed;
    /**
     * @var int|null $httpcode HTTP response code from cURL. Null if curl_exec wasn't called
     * @var int|null $curl_errno cURL error number. Null if curl_exec wasn't called
     */
    public $httpcode, $curl_errno;

    /**
     * @param string $content
     * @param bool $curl_executed Defaults to false
     * @param int|null $httpcode (optional)
     * @param int|null $curl_errno (optional)
     */
    public function __construct(string $content, bool $curl_executed = false, $httpcode = null, $curl_errno = null)
    {
        $this->content = $content;
        $this->curl_executed = $curl_executed;
        $this->httpcode = $httpcode;
        $this->curl_errno = $curl_errno;
    }
}

/**
 * Gets the first entry from the API cache that has a matching endpoint. Will not return if the entry is expired.
 * @param string $endpoint The endpoint for the API request
 * @param resource|CurlHandle $ch The Curl Handle to send the request
 * @param int|null $configId (optional) The config ID for the cached response. Optional if result won't be cached
 * @return CacheResult An object containing the response content, and some cURL info if curl_exec was called
 */
function get_valid_cache_entry(string $endpoint, $ch, $configId = null)
{
    global $db;
    if (preg_match(IGNORED_ENDPOINTS, $endpoint)) {
        return new CacheResult(curl_exec($ch),true,curl_getinfo($ch,CURLINFO_HTTP_CODE),curl_errno($ch));
    }
    $q = $db->query("SELECT
                                * 
                            FROM 
                                api_cache
                            JOIN 
                                outgoing_request_cache_config 
                            ON 
                                api_cache.config_id = outgoing_request_cache_config.id 
                            WHERE 
                                endpoint = '{$db->real_escape_string($endpoint)}'");
    if ($q) {
        $r = $q->fetch_array(MYSQLI_ASSOC);
        $ttl = $r['ttl'];
        $now = time();
        $retrievalTime = $r['last_successfully_retrieved'];
        if ($retrievalTime !== null && $now - strtotime($retrievalTime . 'UTC') < $ttl) {
            return new CacheResult($r['content']);
        }
    }
    $result = curl_exec($ch);
    // add to cache if no errors and HTTP OK
    if (!curl_errno($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) {
        insert_cached($endpoint, $result, $configId);
    }

    return new CacheResult(curl_exec($ch),true,curl_getinfo($ch,CURLINFO_HTTP_CODE),curl_errno($ch));}

/**
 * Adds or updates an entry into the cache table.
 * @param string $endpoint The endpoint for the API request
 * @param string $content The content of API request's response
 * @param int $config_id The ID of the config or its name in the config table
 */
function insert_cached(string $endpoint, string $content, int $config_id)
{
    global $db;
    $r = get_cached_api_response($endpoint);
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
        $queryString = "INSERT INTO
                            api_cache
                            (endpoint, last_successfully_retrieved, last_attempted_retrieval, config_id, content)
                        VALUES
                            ('{$db->real_escape_string($endpoint)}', UTC_TIMESTAMP, UTC_TIMESTAMP,
                             {$config_id}, '{$db->real_escape_string($content)}')";
    }
    $q = $db->query($queryString);
    if (!$q) {
        error_log('Failed to update API cache: ' . $db->error);
    }
}

///**
// * Deletes an entry from the cache or deletes all entries from the cache.
// * @param string|null $endpoint The endpoint of the API request to be removed. If null, empties the cache
// */
//function clearCached(string $endpoint = null){
//    global $db;
//    if ($endpoint) {
//        $queryString = "DELETE FROM api_cache WHERE endpoint = '" . $db->real_escape_string($endpoint) . "'";
//    } else{
//        $queryString = "DELETE FROM api_cache";
//    }
//    $q = $db->query($queryString);
//    if ($q === false) {
//        error_log("Failed to clear API cache: " . $db->error);
//    }
//}

/**
 * Adds a new cache config to the config table.
 * @param int $ttl The time to live for cached entries, measured in seconds
 * @param string $config_name The name of the config
 * @return bool|mysqli_result The results of the MySQLi query
 */
function add_new_cache_config(int $ttl, string $config_name)
{
    global $db;
    return $db->query('INSERT INTO outgoing_request_cache_config (ttl, config_name) VALUES (' . $ttl . ', "' . $db->real_escape_string($config_name) . '")');
}

/**
 * Updates the information of one of the cache configs. Does nothing if both $config_name and $ttl are null
 * @param int $id The ID of the config to be updated
 * @param string|null $config_name (optional) The name of the config
 * @param int|null $ttl (optional) The time to live for cached entries, measured in seconds
 */
function update_cache_config(int $id, $config_name = null, $ttl = null)
{
    global $db;
    if ($config_name === null) {
        if ($ttl === null)
            return;
        $queryString = 'UPDATE outgoing_request_cache_config SET ttl = ' . $ttl . ' WHERE id = ' . $id;
    } else if ($ttl === null) {
        $queryString = "UPDATE outgoing_request_cache_config SET config_name = '{$db->real_escape_string($config_name)}' WHERE id = {$id}";
    } else {
        $queryString = "UPDATE outgoing_request_cache_config SET ttl= {$ttl}, config_name = '{$db->real_escape_string($config_name)}' WHERE id = {$id}";
    }
    $q = $db->query($queryString);
    if (!$q) {
        error_log('Failed to update API cache config: ' . $db->error);
    }
}

