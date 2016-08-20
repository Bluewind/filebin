<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_allow_null_values_userinfo extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'users"
					ALTER COLUMN "username" DROP NOT NULL,
					ALTER COLUMN "password" DROP NOT NULL,
					ALTER COLUMN "email" DROP NOT NULL;
				');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'users`
					CHANGE `username` `username` varchar(32) NULL,
					CHANGE `password` `password` varchar(255) NULL,
					CHANGE `email` `email` varchar(255) NULL;
				');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
