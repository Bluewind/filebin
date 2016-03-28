<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_increase_password_length extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'users"
					ALTER COLUMN "password" type varchar(255);
				');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'users`
					CHANGE `password` `password` varchar(255);
				');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
