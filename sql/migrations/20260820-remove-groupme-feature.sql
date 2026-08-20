-- URW-220: remove the decommissioned GroupMe integration.
-- GroupMe was replaced by Discord + Twilio (URW-210). The GroupMe endpoint/bot
-- code has been deleted; these tables backed it and are now unused.
DROP TABLE IF EXISTS `officers_groupme_access_tokens`;
DROP TABLE IF EXISTS `officers_groupme_muted`;
