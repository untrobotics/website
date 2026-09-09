-- URW-242: attach dyndns keys to members. Each is_admin user can self-generate
-- one personal (unrestricted) key from their profile page. The legacy global
-- key(s) keep uid NULL. UNIQUE(uid) enforces at most one key per member;
-- MySQL allows multiple NULLs, so any number of global keys still coexist.
ALTER TABLE `dyndns_api_keys`
  ADD COLUMN `uid` int(11) DEFAULT NULL,
  ADD UNIQUE KEY `uq_dyndns_uid` (`uid`);
