<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_referrers extends CI_Migration {

	public function up()
	{
		$this->db->query("
			CREATE TABLE `invitations` (
				`user` int(8) unsigned NOT NULL,
				`key` varchar(16) CHARACTER SET ascii NOT NULL,
				`date` int(11) unsigned NOT NULL,
				PRIMARY KEY (`key`),
		KEY `user` (`user`),
		KEY `date` (`date`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin
		");
		$this->db->query("
			ALTER TABLE `users`
			ADD `referrer` INT(8) UNSIGNED NOT NULL DEFAULT '0'
		");
	}

	public function down()
	{
		$this->db->query("
			ALTER TABLE `users`
			DROP `referrer`
		");
		$this->dbforge->drop_table('invitations');

	}
}
