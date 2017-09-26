<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_update_session_table extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'ci_sessions"
					DROP COLUMN "user_agent";
				');
			$this->db->query('
				ALTER TABLE "'.$prefix.'ci_sessions"
					RENAME COLUMN "session_id" TO "id";
				');
			$this->db->query('
				ALTER TABLE "'.$prefix.'ci_sessions"
					RENAME COLUMN "last_activity" TO "timestamp";
				');
			$this->db->query('
				ALTER TABLE "'.$prefix.'ci_sessions"
					RENAME COLUMN "user_data" TO "data";
				');
			$this->db->query('
				ALTER TABLE "'.$prefix.'ci_sessions" ALTER COLUMN id SET DATA TYPE varchar(128);
				');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'ci_sessions`
					DROP `user_agent`,
					CHANGE `session_id` `id` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
					CHANGE `last_activity` `timestamp` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					CHANGE `user_data` `data` BLOB NOT NULL;
				');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
