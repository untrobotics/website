-- Seed data for LOCAL DEVELOPMENT ONLY. Contains no production PII:
-- the two config tables are real (non-sensitive) prod config; everything else
-- is synthetic test data. Runs after 01-schema.sql on first container init.

SET FOREIGN_KEY_CHECKS = 0;

-- Real, non-sensitive configuration pulled from production --------------------
INSERT INTO `dues_config` (`key`, `value`) VALUES
  ('semester_price', '10'),
  ('t_shirt_dues_purchase_price', '15');

INSERT INTO `outgoing_request_cache_config` (`id`, `ttl`, `config_name`, `endpoint`) VALUES
  (8,  86400, 'printful', 'https://api.printful.com/products/$1'),
  (9,  86400, 'printful', 'https://api.printful.com/products/variant/$1'),
  (10, 86400, 'printful', 'https://api.printful.com/store/products/$1'),
  (11, 86400, 'printful', 'https://api.printful.com/store/products$1'),
  (12, 86400, 'printful', 'https://api.printful.com/store/variants/$1');

-- Synthetic test data ---------------------------------------------------------
-- Test member. password is a placeholder bcrypt hash, not a working login.
INSERT INTO `users`
  (`id`, `name`, `email`, `phone`, `unteuid`, `grad_term`, `grad_year`, `password`, `sandbox`)
VALUES
  (1, 'Test Member', 'test@example.com', '9400000000', 'tst0001', 1, 2027,
   '$2y$10$wH8Qb0PlAcEhOlDeRhAsHvneIuL0kQp6Zr3l1bXy9mN0oP2qR4sUe', 0);

-- Botathon team matching the driver-ws relay protocol (ESP32_TEAM_<team_num>).
INSERT INTO `botathon_teams` (`id`, `team_name`, `team_num`, `secret_key`) VALUES
  (1, 'Test Team', 1, 'test-secret');

INSERT INTO `botathon_team_member` (`id`, `uid`, `team_num`) VALUES
  (1, 1, 1);

INSERT INTO `botathon_registration`
  (`id`, `name`, `email`, `classification`, `major`, `team_name`, `season`)
VALUES
  (1, 'Test Member', 'test@example.com', 'Freshman', 'Computer Science', 'Test Team', 5);

SET FOREIGN_KEY_CHECKS = 1;
