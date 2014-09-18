<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_filesize extends CI_Migration {

	public function up()
	{
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "files"
				ADD "filesize" integer NOT NULL
			');
		} else {
			$this->db->query("
				ALTER TABLE `files`
				ADD `filesize` INT UNSIGNED NOT NULL
			");
		}
	}

	public function down()
	{
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "files" DROP "filesize"
			');
		} else {
			$this->db->query('
				ALTER TABLE `files` DROP `filesize`
			');
		}
	}
}
