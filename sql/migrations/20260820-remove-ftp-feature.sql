-- Remove the retired web-based FTP-user feature (obsolete after the move to
-- containers + git CI/CD). Drops the tables backing admin/add-ftp-user.php.
DROP TABLE IF EXISTS `ftpinvites`;
DROP TABLE IF EXISTS `ftplogs`;
DROP TABLE IF EXISTS `ftpusers`;
