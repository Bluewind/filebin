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
			foreach ([
				['apikeys', 'comment', 'VARCHAR(255)'],
				['files', 'filename', 'VARCHAR(256)'],
			] as $col) {
				$this->db->query('ALTER TABLE `'.$prefix.$col[0].'` CHANGE `'.$col[1].'` `'.$col[1].'` '.$col[2].' CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;');
			}
			$this->db->query('SET FOREIGN_KEY_CHECKS = 1');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
