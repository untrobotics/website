tunnel_api_keys | CREATE TABLE `tunnel_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_value` varchar(40) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `internal_port` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1
