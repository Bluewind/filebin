-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Erstellungszeit: 05. Januar 2010 um 17:40
-- Server Version: 5.1.41
-- PHP-Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Datenbank: `filebin`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `files`
--

CREATE TABLE IF NOT EXISTS `files` (
  `hash` varchar(32) COLLATE utf8_bin NOT NULL,
  `id` varchar(8) COLLATE utf8_bin NOT NULL,
  `filename` varchar(256) COLLATE utf8_bin NOT NULL,
  `password` varchar(40) COLLATE utf8_bin DEFAULT NULL,
  `date` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `hash` (`hash`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
