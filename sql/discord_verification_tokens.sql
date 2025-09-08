DROP TABLE IF EXISTS `discord_verification_tokens`;
CREATE TABLE `discord_verification_tokens`(
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `token` varchar(6) NOT NULL,
    `expires_on` timestamp NOT NULL,
    `discord_id` varchar(19) NOT NULL,
    `user_id` int(11) NOT NULL,
    `unt_email` varchar(255) NOT NULL,
    PRIMARY KEY(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;