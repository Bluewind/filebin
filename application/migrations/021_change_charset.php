<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_change_charset extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			# nothing to do
		} else {
			$this->db->query('SET FOREIGN_KEY_CHECKS = 0');
			foreach (['actions', 'apikeys', 'files', 'file_storage', 'multipaste', 'multipaste_file_map', 'profiles', 'users'] as $table) {
				$this->db->query('
					ALTER TABLE `'.$prefix.$table.'` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
				');
			}
			$this->db->query('SET FOREIGN_KEY_CHECKS = 1');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
