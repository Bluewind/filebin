<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Drop_file_password extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('ALTER TABLE "'.$prefix.'files" DROP "password"');
		} else {
			$this->db->query('ALTER TABLE `'.$prefix.'files` DROP `password`;');
		}
	}

	public function down()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'files"
					ADD "password" character varying(40) DEFAULT NULL
			');
		} else {
			$this->db->query("
				ALTER TABLE `'.$prefix.'files`
					ADD `password` varchar(40) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL;
			");
		}
	}
}
