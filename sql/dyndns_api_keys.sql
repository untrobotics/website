dyndns_api_keys | CREATE TABLE `dyndns_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_value` varchar(40) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `subdomain_restrictions` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1
