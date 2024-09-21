INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, variant_name)
VALUES ('dues', 10.00, 0.00, 0.00, 'Dues', '1 semester');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, variant_name)
VALUES ('dues', 20.00, 0.00, 0.00, 'Dues', '2 semester');

-- official name is "UNT Robotics Standard Embroidered T-Shirt"
INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512951', 'XS', 'Shirt');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512952', 'S', 'Shirt');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512953', 'M', 'Shirt');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512954', 'L', 'Shirt');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512955', 'XL', 'Shirt');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512957', '2XL', 'Shirt');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512962', '3XL', 'Shirt');

INSERT INTO paypal_items(item_type, sales_price, cost, tax, item_name, discount, discount_required_item, external_id, variant_name, item_category)
VALUES ('printful_product', 25.00, 0.00, 0.00, 'Dues Shirt', 10.00, 'dues', '3508512963', '4XL', 'Shirt');

INSERT INTO paypal_items_config(ttl, config_name)
VALUES(86400, 'printful_product');