-- URW-233: the admin email log needs a send time. sent_emails never had one, so
-- add created_at. Existing rows get the migration time (their true order is still
-- preserved by the auto-increment id); every new email is stamped correctly.
ALTER TABLE `sent_emails`
    ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP;
