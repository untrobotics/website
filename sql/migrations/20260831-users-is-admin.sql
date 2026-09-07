-- URW-227: add the is_admin flag the admin gate (template/top.php auth(2)) checks.
-- The access-control gate referenced users.is_admin but the column was never
-- created, which locked everyone out of /admin/*. Default 0; admins are granted
-- per-environment (a specific user id) out of band, not in this migration.
ALTER TABLE `users` ADD COLUMN `is_admin` tinyint(1) NOT NULL DEFAULT 0;
