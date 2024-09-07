CREATE TABLE `paypal_transactions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `custom_id`             varchar(255) NOT NULL,  -- This is the ID that PayPal receives for transactions; we generate this
    `paypal_order_id`       varchar(255) NOT NULL,  -- This is the order ID that PayPal generates
    `creation_date`         datetime NOT NULL DEFAULT NOW(),
    `payment_received`      bool NOT NULL DEFAULT FALSE,
    `payment_receipt_date`  datetime,   -- The datetime when payment was captured
    `item_type`             enum('dues', 'prtinful_product', 'donation') NOT NULL,
    `custom_data`           text,   -- Custom info e.g., Printful product ID
    `fulfillment_status`    enum('await_payment', 'fulfilled', 'error') NOT NULL DEFAULT 'await_payment',   -- fulfilled means we've completed our side of the transaction (e.g., made a Printful order or gave the user Good Standing status
    PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=latin1;

/*CREATE TABLE `paypal_orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `paypal_id` varchar(255) NOT NULL,                                  -- The order ID PayPal generates
)*/