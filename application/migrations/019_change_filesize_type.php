<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_change_filesize_type extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'file_storage"
					ALTER "filesize" TYPE bigint;
				');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'file_storage`
					MODIFY `filesize` bigint;
				');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
