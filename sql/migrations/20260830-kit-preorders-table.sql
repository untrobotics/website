-- URW-224: Robot Car Kit preorders. Fixed-price ($40) prepayment with in-person
-- pickup at general meetings. Buyer name/email are collected on the preorder form
-- (carried through the payment as `custom`), not from the payment provider.
CREATE TABLE IF NOT EXISTS `kit_preorders` (
    `id`          int(11) NOT NULL AUTO_INCREMENT,
    `first_name`  varchar(255) DEFAULT NULL,
    `last_name`   varchar(255) DEFAULT NULL,
    `email`       varchar(255) DEFAULT NULL,
    `amount`      decimal(10,2) NOT NULL,
    `fee`         decimal(10,2) NOT NULL DEFAULT '0.00',
    `txid`        varchar(255) NOT NULL,
    `status`      enum('paid','ready','picked_up') NOT NULL DEFAULT 'paid',
    `ready_at`    timestamp NULL DEFAULT NULL,
    `ordered_at`  timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `refunded`    tinyint(1) NOT NULL DEFAULT '0',
    `refunded_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
