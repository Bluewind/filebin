<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_apikeys_add_access_level extends CI_Migration {

	public function up()
	{
		if ($this->db->dbdriver == 'postgre')
		{
			$this->db->query('
				alter table "apikeys" add "access_level" varchar(255) default \'apikey\'
			');
		}
		else
		{
			$this->db->query("
				alter table `apikeys` add `access_level` varchar(255) default 'apikey';
			");
		}
	}

	public function down()
	{
		if ($this->db->dbdriver == 'postgre')
		{
			$this->db->query('
				alter table "apikeys" drop "access_level"
			');
		}
		else
		{
			$this->db->query('
				alter table `apikeys` drop `access_level`
			');
		}
	}
}
