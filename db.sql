SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `files` (
  `hash` varchar(32) COLLATE utf8_bin NOT NULL,
  `id` varchar(8) COLLATE utf8_bin NOT NULL,
  `filename` varchar(256) COLLATE utf8_bin NOT NULL,
  `password` varchar(40) COLLATE utf8_bin DEFAULT NULL,
  `date` int(11) NOT NULL,
  `mimetype` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `hash` (`hash`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
