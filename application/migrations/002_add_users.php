<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_users extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				CREATE TABLE IF NOT EXISTS "'.$prefix.'users" (
					"id" serial PRIMARY KEY,
					"username" character varying(32) NOT NULL,
					"password" character varying(60) NOT NULL,
					"email" character varying(255) NOT NULL
				)
			');

			$this->db->query('
				CREATE TABLE IF NOT EXISTS "'.$prefix.'ci_sessions" (
					"session_id" character varying(40) NOT NULL DEFAULT 0,
					"ip_address" character varying(16) NOT NULL DEFAULT 0,
					"user_agent" character varying(120) NOT NULL,
					"last_activity" integer NOT NULL DEFAULT 0,
					"user_data" text NOT NULL,
					PRIMARY KEY ("session_id")
				);
				CREATE INDEX "ci_sessions_last_activity_idx" ON "'.$prefix.'ci_sessions" ("last_activity");
			');

			$this->db->query('
				ALTER TABLE "'.$prefix.'files" ADD "user" integer NOT NULL DEFAULT 0;
				CREATE INDEX "user_idx" ON "'.$prefix.'files" ("user");
			');

		} else {

			$this->db->query('
				CREATE TABLE IF NOT EXISTS `'.$prefix.'users` (
					`id` int(8) UNSIGNED NOT NULL AUTO_INCREMENT,
					`username` varchar(32) COLLATE ascii_general_ci NOT NULL,
					`password` varchar(60) COLLATE ascii_general_ci NOT NULL,
					`email` varchar(255) COLLATE ascii_general_ci NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			');

			$this->db->query('
				CREATE TABLE IF NOT EXISTS `'.$prefix.'ci_sessions` (
					`session_id` varchar(40) NOT NULL DEFAULT 0,
					`ip_address` varchar(16) NOT NULL DEFAULT 0,
					`user_agent` varchar(120) NOT NULL,
					`last_activity` int(10) unsigned NOT NULL DEFAULT 0,
					`user_data` text NOT NULL,
					PRIMARY KEY (`session_id`),
					KEY `last_activity_idx` (`last_activity`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			');

			$this->db->query('
				ALTER TABLE `'.$prefix.'files`
				ADD `user` INT(8) UNSIGNED NOT NULL DEFAULT 0,
				ADD INDEX (`user`)
			');
		}
	}

	public function down()
	{
		$this->dbforge->drop_table('users');
		$this->dbforge->drop_table('ci_sessions');
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('ALTER TABLE "'.$prefix.'files" DROP "user"');
		} else {
			$this->db->query('ALTER TABLE `'.$prefix.'files` DROP `user`');
		}
	}
}
