<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Repurpose_invitations extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'invitations"
					ADD "action" character varying(255) NOT NULL,
					ADD "data" TEXT NULL;
				CREATE INDEX "'.$prefix.'invitations_action_idx" ON '.$prefix.'invitations ("action");
			');

			$this->db->query('
				UPDATE "'.$prefix.'invitations" SET "action" = \'invitation\' WHERE "action" = \'\'
			');

			$this->db->query('
				ALTER TABLE "'.$prefix.'invitations" RENAME TO '.$prefix.'actions;
			');

		} else {

			$this->db->query('
				ALTER TABLE `'.$prefix.'invitations`
					ADD `action` VARCHAR(255) NOT NULL,
					ADD `data` TEXT NULL,
					ADD INDEX `action` (`action`);
			');

			$this->db->query('
				UPDATE `'.$prefix.'invitations` SET `action` = \'invitation\' WHERE `action` = \'\';
			');

			$this->db->query('
				ALTER TABLE `'.$prefix.'invitations` RENAME `'.$prefix.'actions`;
			');
		}
	}

	public function down()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('ALTER TABLE "'.$prefix.'actions" RENAME TO "'.$prefix.'invitations"');
			$this->db->query('
				ALTER TABLE "'.$prefix.'invitations"
					DROP "action",
					DROP "data";
			');

		} else {

			$this->db->query('ALTER TABLE `'.$prefix.'actions` RENAME `'.$prefix.'invitations`');
			$this->db->query('
				ALTER TABLE `'.$prefix.'invitations`
					DROP `action`,
					DROP `data`;
			');
		}

	}
}
