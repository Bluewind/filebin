<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_filesize extends CI_Migration {

	public function up()
	{
		$this->db->query("
			ALTER TABLE `files`
			ADD `filesize` INT UNSIGNED NOT NULL
		");
	}

	public function down()
	{
		$this->db->query("
			ALTER TABLE `files`
			DROP `filesize`
		");

	}
}
