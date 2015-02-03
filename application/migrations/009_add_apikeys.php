<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_apikeys extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				CREATE TABLE "'.$prefix.'apikeys" (
					"key" varchar(64) NOT NULL,
					"user" integer NOT NULL,
					"created" timestamp WITH TIME ZONE NOT NULL DEFAULT NOW(),
					"comment" varchar(255) NOT NULL,
					PRIMARY KEY ("key")
				);
				CREATE INDEX "apikeys_user_idx" ON "'.$prefix.'apikeys" ("user");
			');

		} else {

			$this->db->query('
				CREATE TABLE `'.$prefix.'apikeys` (
					`key` varchar(64) COLLATE utf8_bin NOT NULL,
					`user` int(8) unsigned NOT NULL,
					`created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`comment` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
					PRIMARY KEY (`key`),
					KEY `user` (`user`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
			');
		}
	}

	public function down()
	{
		$this->dbforge->drop_table('apikeys');
	}
}
