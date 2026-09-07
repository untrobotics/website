-- URW-234: generic key/value settings so operational values (like the current
-- Botathon season) can be changed from the admin panel instead of a redeploy.
-- Seeds botathon_season = 8 (it was stuck at the config default of 5).
CREATE TABLE IF NOT EXISTS `app_settings` (
    `key`        varchar(64)  NOT NULL,
    `value`      varchar(255) NOT NULL,
    `updated_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `app_settings` (`key`, `value`) VALUES ('botathon_season', '8')
    ON DUPLICATE KEY UPDATE `value` = `value`;
