# INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products');
# INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/products/');

-- Individual product page endpoints
    -- Hats
        -- UNT Robotics Cuffed Beanie
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Cuffed Beanie','https://api.printful.com/store/products/@5f6ceb2cf3a5e5');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Cuffed Beanie (Catalog)','https://api.printful.com/products/266');
        -- UNT Robotics Beanie
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Beanie','https://api.printful.com/store/products/@5f6ce5cca7cd98');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Beanie (Catalog)','https://api.printful.com/products/81');
        -- UNT Robotics Pom Pom Beanie
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Pom Pom Beanie','https://api.printful.com/store/products/@5f6ce4b4acbe64');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Pom Pom Beanie (Catalog)','https://api.printful.com/products/93');
        -- UNT Robotics Snapback Front Logo Only
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Snapback Front Logo Only','https://api.printful.com/store/products/@5f6ce6b2c96a55');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Snapback Front Logo Only (Catalog)','https://api.printful.com/products/77');
        -- UNT Robotics Baseball Cap
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Baseball Cap','https://api.printful.com/store/products/@5f5a8f842802b1');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Baseball Cap (Catalog)','https://api.printful.com/products/206');
        -- UNT Robotics Snapback Front & Back Logo
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Snapback Front & Back Logo','https://api.printful.com/store/products/@5f5a8f462d3964');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Snapback Front & Back Logo (Catalog)','https://api.printful.com/products/77');
    -- Trousers
        -- UNT Robotics Sweatpants
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Sweatpants','https://api.printful.com/store/products/@5f6d7466124ec4');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Sweatpants (Catalog)','https://api.printful.com/products/342');
    -- Gear
        -- Aerospace Division T
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Aerospace Division T','https://api.printful.com/store/products/@636ea56986bad9');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Aerospace Division T (Catalog)','https://api.printful.com/products/71');
        -- UNT Robotics High Top Men's Canvas Shoes
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful High Top Men''s Canvas Shoes','https://api.printful.com/store/products/@61ef91b95fdf32');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful High Top Men''s Canvas Shoes (Catalog)','https://api.printful.com/products/513');
        -- UNT Robotics High Top Women's Canvas Shoes
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful High Top Women''s Canvas Shoes','https://api.printful.com/store/products/@61ef8ee3244566');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful High Top Women''s Canvas Shoes (Catalog)','https://api.printful.com/products/525');
        -- UNT Robotics Drawstring Bag
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Drawstring Bag','https://api.printful.com/store/products/@5f7666019c3354');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Drawstring Bag (Catalog)','https://api.printful.com/products/262');
        -- UNT Robotics Socks
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Socks','https://api.printful.com/store/products/@5f7664e32a1d17');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Socks (Catalog)','https://api.printful.com/products/186');
        -- UNT Robotics Flip-Flops
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Flip-Flops','https://api.printful.com/store/products/@5f766443335fa6');
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful Flip-Flops (Catalog)','https://api.printful.com/products/359');

-- Catalog search endpoints
    # INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products?limit=5&search=(Trousers)');
    # INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products?limit=5&search=(Hat)');
    # INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products?limit=5&search=(Gear)');
    INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products?search=(Trousers)');
    INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products?search=(Hat)');
    INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products?search=(Gear)');

-- Catalog product endpoints
    -- Hats
        -- UNT Robotics Cuffed Beanie
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/194962427');
        -- UNT Robotics Beanie
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/194960016');
        -- UNT Robotics Pom Pom Beanie
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/194959831');
        -- UNT Robotics Snapback Front Logo Only
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/194854253');
        -- UNT Robotics Baseball Cap
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/192857853');
        -- UNT Robotics Snapback Front & Back Logo
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/192857643');
    -- Trousers
        -- UNT Robotics Sweatpants
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/195023481');
    -- Gear
        -- Aerospace Division T
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/291159152');
        -- UNT Robotics High Top Men's Canvas Shoes
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/264529951');
        -- UNT Robotics High Top Women's Canvas Shoes
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/264529433');
        -- UNT Robotics Drawstring Bag
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/195968026');
        -- UNT Robotics Socks
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/195967752');
        -- UNT Robotics Flip-Flops
            INSERT INTO outgoing_request_cache_config (ttl, config_name,endpoint) VALUES (86400, 'Printful','https://api.printful.com/store/products/195967367');