<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_referrers extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				CREATE TABLE "'.$prefix.'invitations" (
					"user" integer NOT NULL,
					"key" character varying(16) NOT NULL,
					"date" integer NOT NULL,
					PRIMARY KEY ("key")
				);
				CREATE INDEX "'.$prefix.'invitations_user_idx" ON "'.$prefix.'invitations" ("user");
				CREATE INDEX "'.$prefix.'invitations_date_idx" ON "'.$prefix.'invitations" ("date");
			');
			$this->db->query('
				ALTER TABLE "'.$prefix.'users"
				ADD "referrer" integer NOT NULL DEFAULT 0
			');

		} else {

			$this->db->query('
				CREATE TABLE `'.$prefix.'invitations` (
					`user` int(8) unsigned NOT NULL,
					`key` varchar(16) CHARACTER SET ascii NOT NULL,
					`date` int(11) unsigned NOT NULL,
					PRIMARY KEY (`key`),
			KEY `user` (`user`),
			KEY `date` (`date`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
			');
			$this->db->query('
				ALTER TABLE `'.$prefix.'users`
				ADD `referrer` INT(8) UNSIGNED NOT NULL DEFAULT 0
			');
		}
	}

	public function down()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'users" DROP "referrer"
			');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'users` DROP `referrer`
			');
		}
		$this->dbforge->drop_table('invitations');

	}
}
