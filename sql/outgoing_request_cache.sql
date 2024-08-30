SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `outgoing_request_cache_config`;
SET FOREIGN_KEY_CHECKS = 1;
CREATE TABLE `outgoing_request_cache_config`(
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ttl` int(6) NOT NULL, -- ttl measured in seconds
    `config_name` varchar(255) NOT NULL,
    `endpoint` varchar(4000) NOT NULL, -- endpoint with '{}' replacing specific info, e.g., https://api.printful.com/store/products/{} instead of https://api.printful.com/store/products/@5f6ceb2cf3a5e5
    PRIMARY KEY (id)
)AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `api_cache`;
CREATE TABLE `api_cache`(
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `config_id` int(11) NOT NULL,
    `last_successfully_retrieved` timestamp DEFAULT CURRENT_TIMESTAMP,
    `last_attempted_retrieval` timestamp DEFAULT CURRENT_TIMESTAMP,
    `retry_count` int DEFAULT 0,
    `endpoint_args` varchar(4000) NOT NULL, -- args used to fill '{}'s in endpoint (e.g., https://api.printful.com/store/$1/$2/not-a-real-endpoint?$3) delimited with a '|'
    `content` text NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (config_id) REFERENCES outgoing_request_cache_config(id)
)AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
