<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_profiles extends CI_Migration {

	public function up()
	{
		if ($this->db->dbdriver == 'postgre')
		{
			$this->db->query('
				CREATE TABLE "profiles" (
					"user" integer NOT NULL,
					"upload_id_limits" varchar(255) NOT NULL,
					PRIMARY KEY ("user")
				)
			');

			$this->db->query('
				ALTER TABLE "files" ALTER COLUMN "id" TYPE varchar(255);
			');
		}
		else
		{
			$this->db->query("
				CREATE TABLE `profiles` (
					`user` int(8) unsigned NOT NULL,
					`upload_id_limits` varchar(255) COLLATE utf8_bin NOT NULL,
					PRIMARY KEY (`user`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
				");

			$this->db->query("
				ALTER TABLE `files` CHANGE `id` `id` VARCHAR( 255 );
			");
		}
	}

	public function down()
	{
		if ($this->db->dbdriver == 'postgre')
		{
			$this->db->query('
				DROP TABLE "profiles";
			');
			$this->db->query('
				ALTER TABLE "files" ALTER COLUMN "id" TYPE varchar(6);
			');
		}
		else
		{
			$this->db->query("
				DROP TABLE `profiles`;
			");
			$this->db->query("
				ALTER TABLE `files` CHANGE `id` `id` VARCHAR( 6 );
			");
		}
	}
}
