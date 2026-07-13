-- Records general sponsorship/donation payments (variable amount, no fulfillment).
CREATE TABLE IF NOT EXISTS `donations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `amount` decimal(10,2) NOT NULL,
    `fee` decimal(10,2) NOT NULL DEFAULT '0.00',
    `txid` varchar(255) DEFAULT NULL,
    `donated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
