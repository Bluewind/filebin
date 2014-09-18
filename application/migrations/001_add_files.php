<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_files extends CI_Migration {

	public function up()
	{
		// Set database engine for MySQL drivers
		if (strpos($this->db->dbdriver, 'mysql') !== FALSE)
		{
			$this->db->query('SET storage_engine=MYISAM');
		}

		$this->dbforge->add_field([
			'hash'     => [ 'type' => 'varchar', 'constraint' => 32, 'null' => FALSE ],
			'id'       => [ 'type' => 'varchar', 'constraint' => 6, 'null' => FALSE ],
			'filename' => [ 'type' => 'varchar', 'constraint' => 256, 'null' => FALSE ],
			'password' => [ 'type' => 'varchar', 'constraint' => 40, 'null' => TRUE ],
			'date'     => [ 'type' => 'integer', 'unsigned' => TRUE, 'null' => FALSE ],
			'mimetype' => [ 'type' => 'varchar', 'constraint' => 255, 'null' => FALSE ],
		]);
		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->add_key([ 'hash', 'id' ]);
		$this->dbforge->create_table('files', TRUE);
	}

	public function down()
	{
		$this->dbforge->drop_table('files');
	}
}
