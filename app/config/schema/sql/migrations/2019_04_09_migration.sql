CREATE TABLE `log` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `channel` varchar(45) CHARACTER SET latin1 NOT NULL DEFAULT 'default',
  `description` varchar(255) CHARACTER SET latin1 NOT NULL,
  `ip` varchar(15) CHARACTER SET latin1 NOT NULL,
  `client_date` datetime DEFAULT NULL,
  `server_date` datetime NOT NULL,
  `content` text CHARACTER SET latin1,
  PRIMARY KEY (`id`),
  KEY `ip` (`ip`),
  KEY `server_date` (`server_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
