-- URW-226: kit preorders collect a required phone number; email becomes optional.
-- Phone is stored digits-only (the create endpoints normalize it) and is the
-- 1-per-person dedup key.
ALTER TABLE `kit_preorders` ADD COLUMN `phone` varchar(32) DEFAULT NULL AFTER `last_name`;
