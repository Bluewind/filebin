<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Drop_file_password extends CI_Migration {

	public function up()
	{
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('ALTER TABLE "files" DROP "password"');
		} else {
			$this->db->query("ALTER TABLE `files` DROP `password`;");
		}
	}

	public function down()
	{
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "files"
					ADD "password" character varying(40) DEFAULT NULL
			');
		} else {
			$this->db->query("
				ALTER TABLE `files`
					ADD `password` varchar(40) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL;
			");
		}
	}
}
