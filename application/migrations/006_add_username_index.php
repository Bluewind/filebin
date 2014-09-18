<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_username_index extends CI_Migration {

	public function up()
	{
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				CREATE UNIQUE INDEX "users_username_idx" ON "users" ("username")
			');
		} else {
			$this->db->query("
				ALTER TABLE `users`
				ADD UNIQUE `username` (`username`);
			");
		}
	}

	public function down()
	{
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('DROP INDEX "users_username_idx"');
		} else {
			$this->db->query("
				ALTER TABLE `users`
				DROP INDEX `username`;
			");
		}
	}
}
