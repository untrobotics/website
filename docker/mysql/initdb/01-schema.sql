
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `api_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_id` int(11) NOT NULL,
  `last_successfully_retrieved` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_attempted_retrieval` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `retry_count` int(11) DEFAULT '0',
  `endpoint_args` varchar(4000) NOT NULL,
  `content` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `config_id` (`config_id`),
  CONSTRAINT `api_cache_ibfk_1` FOREIGN KEY (`config_id`) REFERENCES `outgoing_request_cache_config` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6575 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auth_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_sessions` (
  `id` int(6) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) NOT NULL,
  `session_name` varchar(255) NOT NULL,
  `fingerprint` varchar(32) NOT NULL,
  `uid` int(6) DEFAULT NULL,
  `expires` int(11) DEFAULT NULL,
  `session` int(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=727 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `botathon_registration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `botathon_registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `classification` varchar(25) DEFAULT NULL,
  `major` varchar(255) DEFAULT NULL,
  `diet_restrictions` text,
  `latex_allergy` int(1) DEFAULT NULL,
  `unteuid` varchar(10) DEFAULT NULL,
  `reg_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `team_name` varchar(255) DEFAULT NULL,
  `disability_accommodations` varchar(2555) DEFAULT NULL,
  `season` int(7) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=121 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `botathon_team_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `botathon_team_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `team_num` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `botathon_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `botathon_teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_name` varchar(50) DEFAULT NULL,
  `team_num` int(11) DEFAULT NULL,
  `secret_key` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_form` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `message` text,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=320 DEFAULT CHARSET=latin1;

CREATE TABLE `rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bucket` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `bucket_time` (`bucket`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `dues_alternative_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) NOT NULL,
  `reason` varchar(64) DEFAULT NULL,
  `note` text,
  `status` enum('pending','approved','denied') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `uid` (`uid`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `newsletter_signups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribed` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Running tally of Brevo sends per day. email() bumps this on a successful Brevo
-- send; the newsletter drip sender reads it to stay under the free-plan daily cap
-- while leaving a reserve for transactional mail.
CREATE TABLE `brevo_daily_sends` (
  `send_date` date NOT NULL,
  `sent` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`send_date`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `newsletter_campaigns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL,
  `body` mediumtext NOT NULL,
  `status` enum('draft','sending','paused','sent') NOT NULL DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_recipients` int(11) NOT NULL DEFAULT '0',
  `sent_count` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `newsletter_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT '0',
  `sent_at` timestamp NULL DEFAULT NULL,
  `error` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_status` (`campaign_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dues_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dues_config` (
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dues_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dues_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `euid` varchar(25) DEFAULT NULL,
  `amount` decimal(5,2) DEFAULT NULL,
  `fee` decimal(5,2) DEFAULT NULL,
  `txid` varchar(255) DEFAULT NULL,
  `payment_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `dues_term` int(1) NOT NULL,
  `dues_year` int(4) NOT NULL,
  `uid` int(11) NOT NULL,
  `refunded` tinyint(1) NOT NULL DEFAULT '0',
  `refunded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_dues_uid` (`uid`),
  CONSTRAINT `fk_dues_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=311 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dyndns_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dyndns_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_value` varchar(40) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `subdomain_restrictions` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ftpinvites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ftpinvites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `registration_token` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ftplogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ftplogs` (
  `msg` varchar(255) DEFAULT NULL,
  `user` char(128) DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `host` char(128) DEFAULT NULL,
  `rhost` char(128) DEFAULT NULL,
  `logtime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ftpusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ftpusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` char(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  `passwd` char(128) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `handled_ipns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `handled_ipns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `handled_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `handled_source` varchar(25) DEFAULT NULL,
  `txid` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB AUTO_INCREMENT=217 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `officers_groupme_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `officers_groupme_access_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `channel_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `officers_groupme_muted`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `officers_groupme_muted` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `orgsync_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `orgsync_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `source` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `outgoing_request_cache_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `outgoing_request_cache_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ttl` int(6) NOT NULL,
  `config_name` varchar(255) NOT NULL,
  `endpoint` varchar(4000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(255) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(50) DEFAULT NULL,
  `host` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid` (`uid`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pending_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` varchar(64) NOT NULL,
  `source` varchar(25) NOT NULL,
  `custom` text NOT NULL,
  `option_pairs` text NOT NULL,
  `expected_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `item_name` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT '1',
  `sandbox` tinyint(1) NOT NULL DEFAULT '0',
  `captured` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `printful_order`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `printful_order` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `confirmed` int(1) NOT NULL DEFAULT '0',
  `order_name` varchar(255) DEFAULT NULL,
  `order_variant_name` varchar(255) DEFAULT NULL,
  `order_type` varchar(255) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `returned` int(1) NOT NULL DEFAULT '0',
  `uid` int(11) DEFAULT NULL,
  `refunded` tinyint(1) NOT NULL DEFAULT '0',
  `refunded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `printful_order_tx`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `printful_order_tx` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `txid` varchar(255) DEFAULT NULL,
  `printful_order_id` varchar(25) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `printful_shipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `printful_shipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) DEFAULT NULL,
  `shipment_id` int(11) DEFAULT NULL,
  `carrier` varchar(50) DEFAULT NULL,
  `service` varchar(50) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `tracking_url` varchar(1000) DEFAULT NULL,
  `ship_date` varchar(25) DEFAULT NULL,
  `ship_time` bigint(20) DEFAULT NULL,
  `reshipment` int(1) DEFAULT '0',
  `confirmed` int(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipment_id` (`shipment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sent_emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sent_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to` text,
  `subject` text,
  `message` text,
  `headers` text,
  `status` int(5) DEFAULT NULL,
  `attachments` text,
  `replyto` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tunnel_api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tunnel_api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key_value` varchar(40) DEFAULT NULL,
  `endpoint` varchar(255) DEFAULT NULL,
  `internal_port` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `unteuid` varchar(7) DEFAULT NULL,
  `grad_term` int(1) DEFAULT NULL,
  `grad_year` int(4) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `reg_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reg_ip` varchar(39) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  `sms_consent` tinyint(1) NOT NULL DEFAULT '0',
  `sandbox` int(1) NOT NULL DEFAULT '0',
  `discord_id` bigint(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discord_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
-- UNT email verification state for the interactive Discord bot (discord-bot/).
-- One row per Discord user. `code_hash` is sha256(code + discord_id + secret) —
-- the plaintext code is NEVER stored. `email` is UNIQUE so an address can bind
-- to only one Discord account (prevents sharing). Rate-limit / lockout counters
-- live here so they survive bot restarts.
CREATE TABLE `discord_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_id` bigint(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `code_hash` varchar(64) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT '0',
  `verified` tinyint(1) NOT NULL DEFAULT '0',
  `verified_at` datetime DEFAULT NULL,
  `last_sent_at` datetime DEFAULT NULL,
  `send_count_window` int(11) NOT NULL DEFAULT '0',
  `window_started_at` datetime DEFAULT NULL,
  `failed_total` int(11) NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discord_id` (`discord_id`),
  UNIQUE KEY `email` (`email`),
  KEY `verified` (`verified`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discord_verification_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
-- Append-only audit trail for the interactive Discord verification bot
-- (discord-bot/). One row per verification attempt/action so the club keeps a
-- full history (who tried, with what email, the outcome, when) alongside the
-- current-state row in `discord_verifications`. This table is NEVER updated or
-- deleted by the bot. The plaintext code and the code hash are NEVER stored
-- here. `action` is 'verify_request' | 'token_attempt'; `outcome` is the
-- terminal result (e.g. sent, invalid_email, bad_domain, already_verified,
-- email_taken, cooldown, hourly_limit, email_failed, success, invalid_code,
-- expired, locked, no_request). `detail` is free-form context (attemptsLeft,
-- retryAfter, burned/locked flags).
CREATE TABLE `discord_verification_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `discord_id` bigint(20) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `action` varchar(32) NOT NULL,
  `outcome` varchar(32) NOT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `discord_id` (`discord_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
--
-- Migration tracking (see sql/migrate.php). A fresh schema is already at the
-- current version, so every existing migration file is pre-recorded as applied.
--
DROP TABLE IF EXISTS `donations`;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `txid` varchar(255) DEFAULT NULL,
  `donated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `refunded` tinyint(1) NOT NULL DEFAULT '0',
  `refunded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `txid` (`txid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `filename` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO `schema_migrations` (`filename`) VALUES
('URW-64-ftp-users.sql'),
('URW-65.sql'),
('URW-66.sql'),
('URW-147.sql'),
('URW-178-cache-configs.sql'),
('stripe-payments-txid-and-order-uid.sql'),
('20260710-users-sms-consent.sql'),
('20260713-donations-table.sql');

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

