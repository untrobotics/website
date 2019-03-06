CREATE TABLE `dues_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `euid` varchar(25) DEFAULT NULL,
  `amount` decimal(5,2) DEFAULT NULL,
  `fee` decimal(5,2) DEFAULT NULL,
  `txid` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1
