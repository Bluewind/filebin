<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_filesize extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'files"
				ADD "filesize" integer NOT NULL
			');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'files`
				ADD `filesize` INT UNSIGNED NOT NULL
			');
		}
	}

	public function down()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'files" DROP "filesize"
			');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'files` DROP `filesize`
			');
		}
	}
}
