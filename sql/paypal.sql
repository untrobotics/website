DROP TABLE IF EXISTS paypal_order_item;
DROP TABLE IF EXISTS paypal_items;
DROP TABLE IF EXISTS paypal_orders;
DROP EVENT IF EXISTS remove_expired_orders;
CREATE TABLE `paypal_orders`
(
    `id`                   int(11)                                  NOT NULL AUTO_INCREMENT,
    `uid`                  int(11)                                  NULL,                       -- The User ID this order belongs to. Can be null for merch, but shouldn't be null for dues
#     `custom_id`            varchar(255) NOT NULL, -- This is the ID that PayPal receives for transactions; we generate this
    `paypal_order_id`      varchar(255)                             NOT NULL,                   -- This is the order ID that PayPal generates
    `creation_date`        datetime                                 NOT NULL DEFAULT NOW(),
    `payment_received`     bool                                     NOT NULL DEFAULT FALSE,
    `payment_receipt_date` datetime                                 NULL,                       -- The datetime when payment was captured
    `status`               enum ('created', 'approved', 'captured') NOT NULL DEFAULT 'created', -- approved means the buyer approved payment, captured means we captured payment
    PRIMARY KEY (`id`),
    FOREIGN KEY (`uid`) REFERENCES users (id)
) ENGINE = InnoDB
  AUTO_INCREMENT = 47
  DEFAULT CHARSET = latin1;


CREATE TABLE `paypal_items_config`
(
    `id`          int(11)      NOT NULL AUTO_INCREMENT,
    `ttl`         int(6)       NOT NULL,
    `config_name` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 47
  DEFAULT CHARSET latin1;

CREATE TABLE `paypal_items`
(
    `id`                     int(11)                NOT NULL AUTO_INCREMENT,
    `item_type`              enum ('dues', 'printful_product', 'donation'),
    `sales_price`            decimal(5, 2) UNSIGNED NOT NULL,
    `discount`               decimal(5, 2) UNSIGNED                        DEFAULT 0.00,
    `discount_required_item` enum ('dues', 'printful_product', 'donation') DEFAULT NULL, -- a required item_type to apply the discount
    `tax`                    decimal(5, 2) UNSIGNED NOT NULL,
    `cost`                   decimal(5, 2) UNSIGNED NOT NULL,                            -- how much the item costs us... dues are $0, whereas a t-shirt might be $15
#   `currency_code` enum ('USD')  NOT NULL DEFAULT 'USD',
#   `description` varchar(127),
    `external_id`            varchar(128)           NULL,   -- variant ID for Printful
    `item_name`              varchar(255)           NOT NULL,                            -- generic name, like "Dues" or "Dues Shirt"
    `variant_name`           varchar(255)           NULL,                                -- Variant name, like "2 semesters" or "XL"
    `item_category`         varchar(25)             NOT NULL,   -- item category name, e.g. Hat or T-Shirt
    `last_updated`           timestamp                                     DEFAULT CURRENT_TIMESTAMP,
    `config_id`              int(11)                NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`config_id`) REFERENCES paypal_items_config (id)
) ENGINE = InnoDB
  AUTO_INCREMENT = 47
  DEFAULT CHARSET = latin1;


CREATE TABLE `paypal_order_item`
(
    `id`                 int(11)                                                                       NOT NULL AUTO_INCREMENT,
    `item_id`            int(11)                                                                       NOT NULL,
    `count`              int(3)                                                                        NOT NULL DEFAULT 1,                -- how many units we owe (e.g., 3 shirts)
    `order_id`           int(11)                                                                       NOT NULL,
    `custom_data`        text                                                                          NULL,                              -- Custom info e.g., Printful product ID
    `fulfillment_status` enum ('await_approval', 'await_payment' ,'in-progress' ,'fulfilled', 'error') NOT NULL DEFAULT 'await_approval', -- fulfilled means we've completed our side of the transaction (e.g., made a Printful order or gave the user Good Standing status
    PRIMARY KEY (`id`),
    FOREIGN KEY (`item_id`) REFERENCES paypal_items (id),
    FOREIGN KEY (`order_id`) REFERENCES paypal_orders (id)

) ENGINE = InnoDB
  AUTO_INCREMENT = 47
  DEFAULT CHARSET = latin1;

DELIMITER |
CREATE EVENT IF NOT EXISTS
    remove_expired_orders
    ON SCHEDULE EVERY 3 DAY
    ON COMPLETION PRESERVE
    DO
    BEGIN
        DELETE
        FROM paypal_order_item
        WHERE order_id
                  IN (SELECT id
                      FROM paypal_orders
                      WHERE status = 'created'
                        AND creation_date <= SUBDATE(NOW(), INTERVAL 6 HOUR));

        DELETE
        FROM paypal_orders
        WHERE status = 'created'
          AND creation_date <= SUBDATE(NOW(), INTERVAL 6 HOUR);
    END |
DELIMITER ;