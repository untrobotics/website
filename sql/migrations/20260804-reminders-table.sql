-- URW-83: /remind — Discord reminders that can repeat until marked done.
-- The bot's scheduler polls `next_fire`; `repeat_seconds` (when set) re-arms the
-- reminder until someone clicks "Mark done" (sets `done = 1`).
CREATE TABLE IF NOT EXISTS `reminders` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `guild_id`       VARCHAR(32)  NOT NULL,
  `channel_id`     VARCHAR(32)  NOT NULL,
  `creator_id`     VARCHAR(32)  NOT NULL,
  `target_type`    ENUM('user','role') NOT NULL DEFAULT 'user',
  `target_id`      VARCHAR(32)  NOT NULL,
  `body`           VARCHAR(1000) NOT NULL,
  `next_fire`      DATETIME     NOT NULL,
  `repeat_seconds` INT UNSIGNED NULL,
  `fire_count`     INT UNSIGNED NOT NULL DEFAULT 0,
  `done`           TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_due` (`done`, `next_fire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
