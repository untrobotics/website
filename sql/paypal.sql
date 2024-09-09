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
    `item_name`              varchar(255)           NOT NULL UNIQUE,
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  AUTO_INCREMENT = 47
  DEFAULT CHARSET = latin1;

CREATE TABLE `paypal_transactions`
(
    `id`                   int(11)                                      NOT NULL AUTO_INCREMENT,
    `uid`                  int(11)                                      NULL ,
    `custom_id`            varchar(255)                                 NOT NULL,                         -- This is the ID that PayPal receives for transactions; we generate this
    `paypal_order_id`      varchar(255)                                 NOT NULL,                         -- This is the order ID that PayPal generates
    `creation_date`        datetime                                     NOT NULL DEFAULT NOW(),
    `payment_received`     bool                                         NOT NULL DEFAULT FALSE,
    `payment_receipt_date` datetime null,                                                                      -- The datetime when payment was captured
    `item_id`              int(11)                                      NOT NULL,
    `custom_data`          text,                                                                          -- Custom info e.g., Printful product ID
    `fulfillment_status`   enum ('await_payment', 'fulfilled', 'error') NOT NULL DEFAULT 'await_payment', -- fulfilled means we've completed our side of the transaction (e.g., made a Printful order or gave the user Good Standing status
    PRIMARY KEY (`id`),
    FOREIGN KEY (`item_id`) REFERENCES paypal_items (id),
    FOREIGN KEY (`uid`) REFERENCES users(id)
) ENGINE = InnoDB
  AUTO_INCREMENT = 47
  DEFAULT CHARSET = latin1;


/*CREATE TABLE `paypal_orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `paypal_id` varchar(255) NOT NULL,                                  -- The order ID PayPal generates
)*/