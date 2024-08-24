<?php
require_once('../template/top.php');

/**
 * Gets the first entry from the API cache that has a matching endpoint.
 * @param string $endpoint The endpoint for the API request
 * @return array|null The first entry that matches or null if no entry exists
 */
function get_cached_api_response(string $endpoint): ?array
{
    global $db;
    $q = $db->query('
                    SELECT 
                        * 
                    FROM 
                        api_cache
                    LEFT JOIN
                        outgoing_request_cache_config
                    ON 
                        api_cache.config_id = outgoing_request_cache_config.id
                    WHERE 
                        endpoint = "' . $db->real_escape_string($endpoint) . '"
                    ');
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
     * @var bool $fetched_new_content true if curl_exec was called, false otherwise
     */
    public $fetched_new_content;
    /**
     * @var int|null $httpcode HTTP response code from cURL. Null if curl_exec wasn't called
     * @var int|null $curl_errno cURL error number. Null if curl_exec wasn't called
     */
    public $http_code, $curl_errno;

    /**
     * @param string $content
     * @param bool $fetched_new_content Defaults to false
     * @param int|null $httpcode (optional)
     * @param int|null $curl_errno (optional)
     */
    public function __construct(string $content, bool $fetched_new_content = false, $httpcode = null, $curl_errno = null)
    {
        $this->content = $content;
        $this->fetched_new_content = $fetched_new_content;
        $this->http_code = $httpcode;
        $this->curl_errno = $curl_errno;
    }
}

/**
 * Gets the first entry from the API cache that has a matching endpoint. If the entry is expired or does not exist, returns the results from executing cURL. Updates the cache with fetched results if the entry existed
 * @param string $endpoint The endpoint for the API request
 * @param resource|CurlHandle $ch The Curl Handle to send the request
 * @return CacheResult An object containing the response content, and some cURL info if curl_exec was called
 */
function get_valid_cache_entry(string $endpoint, $ch)
{
    global $db;
    $q = $db->query("
                    SELECT
                        outgoing_request_cache_config.id as conf_id, ttl, content, last_successfully_retrieved
                    FROM 
                        outgoing_request_cache_config
                    LEFT JOIN 
                        api_cache 
                    ON 
                        outgoing_request_cache_config.id = api_cache.config_id
                    WHERE 
                        endpoint = '{$db->real_escape_string($endpoint)}'
                    ");
    if($q===false){
        error_log("Failed to retrieve endpoint \"{$endpoint}\" from cache table: {$db->error}");
        return null;
    }
    $can_be_cached = $q && $q->num_rows > 0;
    if ($can_be_cached) {
        $r = $q->fetch_array(MYSQLI_ASSOC);
        if($r['content']!==null){
            $ttl = $r['ttl'];
            $now = time();
            $retrieval_time = $r['last_successfully_retrieved'];
            if ($retrieval_time !== null && $now - strtotime($retrieval_time . 'UTC') < $ttl) {
                return new CacheResult($r['content']);
            }
        }
        $config_id = $r['conf_id'];
    }
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    $result = curl_exec($ch);
    // add to cache if old cache entry, no errors, and HTTP OK
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($can_be_cached && !curl_errno($ch) && $response_code >= 200 && $response_code <=299) {
        insert_cached($endpoint, $result, $config_id);
    }

    return new CacheResult(curl_exec($ch),true, $response_code,curl_errno($ch));}

/**
 * Adds or updates an entry into the cache table. Does not check if data should be cached
 * @param string $endpoint The endpoint for the API request
 * @param string $content The content of API request's response
 * @param int $config_id The ID of the config or its name in the config table
 */
function insert_cached(string $endpoint, string $content, int $config_id)
{
    global $db;
    $r = get_cached_api_response($endpoint);
    $query_string = "";
    if ($r) {
        $id = $r['id'];
        $query_string = '
                        UPDATE
                            api_cache 
                        SET 
                            last_successfully_retrieved = UTC_TIMESTAMP, 
                            last_attempted_retrieval = UTC_TIMESTAMP, 
                            retry_count = 0, 
                            content =\'' . $db->real_escape_string($content) . '\'
                        WHERE 
                            id = ' . $id;
    } else {
        $query_string = "
                        INSERT INTO
                            api_cache
                            (last_successfully_retrieved, last_attempted_retrieval, config_id, content)
                        VALUES
                            (UTC_TIMESTAMP, UTC_TIMESTAMP,
                             {$config_id}, '{$db->real_escape_string($content)}')";
    }
    $q = $db->query($query_string);
    if (!$q) {
        error_log("Failed to update API cache for endpoint \"{$endpoint}\": " . $db->error);
    }
}

/**
 * Checks if an endpoint should be cached
 * @param string $endpoint The endpoint to check
 * @return bool|null Returns null on error. Returns true if the endpoint should be cached. Returns false if the endpoint should not be cached
 */
function is_cachable(string $endpoint): ?bool{
    global $db;
    $q = $db->query("
                    SELECT
                        id
                    FROM 
                        outgoing_request_cache_config
                    WHERE
                        endpoint = '{$db->real_escape_string($endpoint)}'
                    ");
    if($q===false){
        error_log("Failed to retrieve endpoint {$endpoint} from cache config table: {$db->error}");
        return null;
    }
    return $q->num_rows > 0;
}

///**
// * Deletes an entry from the cache or deletes all entries from the cache.
// * @param string|null $endpoint The endpoint of the API request to be removed. If null, empties the cache
// */
//function clear_cached(string $endpoint = null){
//    global $db;
//    if ($endpoint) {
//        $query_string = "DELETE FROM api_cache WHERE endpoint = '" . $db->real_escape_string($endpoint) . "'";
//    } else{
//        $query_string = "DELETE FROM api_cache";
//    }
//    $q = $db->query($query_string);
//    if ($q === false) {
//        error_log("Failed to clear API cache: " . $db->error);
//    }
//}

/**
 * Adds a new cache config to the config table.
 * @param int $ttl The time to live for cached entries, measured in seconds
 * @param string $config_name The name of the config
 * @param string $endpoint The endpoint for the config
 * @return bool|mysqli_result The results of the MySQLi query
 */
function add_new_cache_config(int $ttl, string $config_name, string $endpoint)
{
    global $db;
    return $db->query("
                        INSERT INTO 
                            outgoing_request_cache_config (ttl, config_name, endpoint) 
                        VALUES 
                            ({$ttl}, '{$db->real_escape_string($config_name)}', '{$db->real_escape_string($endpoint)}')
                        ");
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
        $query_string = '
                        UPDATE 
                            outgoing_request_cache_config
                        SET 
                            ttl = ' . $ttl . ' 
                        WHERE 
                            id = ' . $id;
    } else if ($ttl === null) {
        $query_string = "
                        UPDATE 
                            outgoing_request_cache_config 
                        SET 
                            config_name = '{$db->real_escape_string($config_name)}' 
                        WHERE 
                            id = {$id}";
    } else {
        $query_string = "UPDATE 
                            outgoing_request_cache_config 
                        SET 
                            ttl= {$ttl}, config_name = '{$db->real_escape_string($config_name)}' 
                        WHERE 
                            id = {$id}";
    }
    $q = $db->query($query_string);
    if (!$q) {
        error_log('Failed to update API cache config: ' . $db->error);
    }
}

