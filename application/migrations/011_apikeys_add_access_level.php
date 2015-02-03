<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_apikeys_add_access_level extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				alter table "'.$prefix.'apikeys" add "access_level" varchar(255) default \'apikey\'
			');
		} else {
			$this->db->query('
				alter table `'.$prefix.'apikeys` add `access_level` varchar(255) default \'apikey\';
			');
		}
	}

	public function down()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				alter table "'.$prefix.'apikeys" drop "access_level"
			');
		} else {
			$this->db->query('
				alter table `'.$prefix.'apikeys` drop `access_level`
			');
		}
	}
}
