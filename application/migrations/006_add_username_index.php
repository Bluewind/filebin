<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_username_index extends CI_Migration {

	public function up()
	{
		$this->db->query("
			ALTER TABLE `users`
			ADD UNIQUE `username` (`username`);
		");
	}

	public function down()
	{
		$this->db->query("
			ALTER TABLE `users`
			DROP INDEX `username`;
		");
	}
}
