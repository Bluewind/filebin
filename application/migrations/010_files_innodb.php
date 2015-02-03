<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_files_innodb extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver != 'postgre') {
			$this->db->query('
				ALTER TABLE `'.$prefix.'files` ENGINE = InnoDB;
			');
		}
	}

	public function down()
	{
	}
}
