<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_multipaste extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				CREATE TABLE "'.$prefix.'multipaste" (
					"url_id" varchar(255) NOT NULL PRIMARY KEY,
					"multipaste_id" serial UNIQUE,
					"user_id" integer NOT NULL,
					"date" integer NOT NULL
				);
				CREATE INDEX "'.$prefix.'multipaste_user_idx" ON "'.$prefix.'multipaste" ("user_id");
			');

			$this->db->query('
				CREATE TABLE "'.$prefix.'multipaste_file_map" (
					"multipaste_id" integer NOT NULL REFERENCES "'.$prefix.'multipaste" ("multipaste_id") ON DELETE CASCADE ON UPDATE CASCADE,
					"file_url_id" varchar(255) NOT NULL REFERENCES "'.$prefix.'files"("id") ON DELETE CASCADE ON UPDATE CASCADE,
					"sort_order" serial PRIMARY KEY,
					UNIQUE ("multipaste_id", "file_url_id")
				);
				CREATE INDEX "'.$prefix.'multipaste_file_map_file_idx" ON "'.$prefix.'multipaste_file_map" ("file_url_id");
			');

		} else {

			$this->db->query('
			CREATE TABLE `'.$prefix.'multipaste` (
				`url_id` varchar(255) NOT NULL,
				`multipaste_id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`date` int(11) NOT NULL,
				PRIMARY KEY (`url_id`),
				UNIQUE KEY `multipaste_id` (`multipaste_id`),
				KEY `user_id` (`user_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8;');

			$this->db->query('
			CREATE TABLE `'.$prefix.'multipaste_file_map` (
				`multipaste_id` int(11) NOT NULL,
				`file_url_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
				`sort_order` int(10) unsigned NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`sort_order`),
				UNIQUE KEY `multipaste_id` (`multipaste_id`,`file_url_id`),
				KEY `multipaste_file_map_ibfk_2` (`file_url_id`),
				CONSTRAINT `'.$prefix.'multipaste_file_map_ibfk_1` FOREIGN KEY (`multipaste_id`) REFERENCES `'.$prefix.'multipaste` (`multipaste_id`) ON DELETE CASCADE ON UPDATE CASCADE,
				CONSTRAINT `'.$prefix.'multipaste_file_map_ibfk_2` FOREIGN KEY (`file_url_id`) REFERENCES `'.$prefix.'files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
