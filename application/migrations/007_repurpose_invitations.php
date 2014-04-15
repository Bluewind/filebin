<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Repurpose_invitations extends CI_Migration {

	public function up()
	{
		$this->db->query("
			ALTER TABLE `invitations`
				ADD `action` VARCHAR(255) NOT NULL,
				ADD `data` TEXT NULL,
				ADD INDEX `action` (`action`);
		");

		$this->db->query("
			UPDATE `invitations` SET `action` = 'invitation' WHERE `action` = '';
		");

		$this->db->query("
			ALTER TABLE `invitations` RENAME `actions`;
		");

	}

	public function down()
	{
		$this->db->query("
			ALTER TABLE `actions` RENAME `invitations`;
		");

		$this->db->query("
			ALTER TABLE `invitations`
				DROP `action`,
				DROP `data`;
		");
	}
}
