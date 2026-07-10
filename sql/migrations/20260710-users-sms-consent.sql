-- Optional SMS opt-in consent, captured by an unchecked checkbox on the join
-- form. A2P/TCR requires that SMS consent NOT be a required condition of joining,
-- so this is stored per-user and defaults to 0 (no consent).
ALTER TABLE users ADD COLUMN sms_consent tinyint(1) NOT NULL DEFAULT 0;
