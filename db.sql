SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `files` (
  `hash` varchar(32) CHARACTER SET ascii NOT NULL,
  `id` varchar(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `filename` varchar(256) COLLATE utf8_bin NOT NULL,
  `password` varchar(40) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `date` int(11) unsigned NOT NULL,
  `mimetype` varchar(255) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  KEY `date` (`date`),
  KEY `hash` (`hash`,`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
