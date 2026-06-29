-- Scrub PII from a dev database after a prod->dev sync. Applied by
-- deploy/sync-prod-to-dev.sh (skipped with --raw). Keeps row shapes/relationships
-- intact while removing real member data, credentials, and tokens.
SET FOREIGN_KEY_CHECKS = 0;

UPDATE `users` SET
  name       = CONCAT('Member ', id),
  email      = CONCAT('user', id, '@example.test'),
  phone      = '9400000000',
  unteuid    = CONCAT('dev', LPAD(id, 4, '0')),
  reg_ip     = '0.0.0.0',
  discord_id = NULL;

UPDATE `botathon_registration` SET
  name = CONCAT('Registrant ', id),
  email = CONCAT('reg', id, '@example.test'),
  phone = '9400000000',
  unteuid = CONCAT('dev', LPAD(id, 4, '0')),
  diet_restrictions = NULL,
  disability_accommodations = NULL;

UPDATE `dues_payments` SET
  name = CONCAT('Member ', uid),
  email = CONCAT('user', uid, '@example.test'),
  euid = CONCAT('dev', LPAD(uid, 4, '0')),
  txid = CONCAT('DEVTX', id);

UPDATE `contact_form` SET
  name = CONCAT('Contact ', id),
  email = CONCAT('contact', id, '@example.test'),
  phone = '', company = '', message = '[redacted]';

UPDATE `orgsync_members` SET
  name = CONCAT('Org Member ', id),
  email = CONCAT('org', id, '@example.test'),
  token = '';

UPDATE `printful_order` SET
  email_address = CONCAT('order', id, '@example.test'),
  first_name = 'Dev', last_name = CONCAT('Order ', id);

UPDATE `sent_emails` SET `to` = 'dev@example.test', subject = '[redacted]',
  message = '[redacted]', headers = '', attachments = NULL, replyto = NULL;

UPDATE `ftpusers` SET name = CONCAT('dev', id), passwd = '';
UPDATE `ftpinvites` SET email = CONCAT('invite', id, '@example.test');

-- Credentials / tokens / sessions: wipe entirely (never wanted in dev).
TRUNCATE `auth_sessions`;
TRUNCATE `password_reset_tokens`;
TRUNCATE `dyndns_api_keys`;
TRUNCATE `tunnel_api_keys`;
TRUNCATE `officers_groupme_access_tokens`;
TRUNCATE `ftplogs`;

SET FOREIGN_KEY_CHECKS = 1;
