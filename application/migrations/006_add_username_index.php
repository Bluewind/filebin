<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_username_index extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				CREATE UNIQUE INDEX "'.$prefix.'users_username_idx" ON "'.$prefix.'users" ("username")
			');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'users`
				ADD UNIQUE `username` (`username`);
			');
		}
	}

	public function down()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('DROP INDEX "'.$prefix.'users_username_idx"');
		} else {
			$this->db->query("
				ALTER TABLE `'.$prefix.'users`
				DROP INDEX `username`;
			");
		}
	}
}
