DROP TABLE IF EXISTS `outgoing_request_cache_config`;
CREATE TABLE `outgoing_request_cache_config`(
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ttl` int(6) NOT NULL DEFAULT 86400, -- ttl measured in seconds
    `config_name` varchar(255) DEFAULT NULL,
    PRIMARY KEY (id)
)ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `api_cache`;
CREATE TABLE `api_cache`(
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `endpoint` varchar(255) DEFAULT NULL,
    `last_successfully_retrieved` datetime,
    `last_attempted_retrieval` datetime DEFAULT UTC_TIMESTAMP(),
    `retry_count` int DEFAULT 0,
    `config_id` int(11) NOT NULL,
    `content` text NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (config_id) REFERENCES outgoing_request_cache_config(id)
)ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
