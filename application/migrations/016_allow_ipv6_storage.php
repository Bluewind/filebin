<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_allow_ipv6_storage extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'ci_sessions"
					ALTER COLUMN "ip_address" type varchar(39);
				');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'ci_sessions`
					CHANGE `ip_address` `ip_address` varchar(39);
				');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
