<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_constraints extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('ALTER TABLE "'.$prefix.'users" ALTER COLUMN "referrer" TYPE integer');
			$this->db->query('ALTER TABLE "'.$prefix.'users" ALTER COLUMN "referrer" DROP NOT NULL');
			$this->db->query('CREATE INDEX "users_referrer_idx" ON "'.$prefix.'users" ("referrer")');
			$this->db->query('UPDATE "'.$prefix.'users" SET "referrer" = NULL where "referrer" = 0');
			$this->db->query('
				ALTER TABLE "'.$prefix.'users"
					ADD CONSTRAINT "'.$prefix.'referrer_user_fkey" FOREIGN KEY ("referrer")
						REFERENCES "'.$prefix.'users"("id") ON DELETE RESTRICT ON UPDATE RESTRICT
			');

		} else {

			$this->db->query('ALTER TABLE `'.$prefix.'users` ADD INDEX(`referrer`);');
			$this->db->query('ALTER TABLE `'.$prefix.'users` CHANGE `referrer` `referrer`
				INT(8) UNSIGNED NULL;');
			$this->db->query('UPDATE `'.$prefix.'users` SET `referrer` = NULL where `referrer` = 0;');
			$this->db->query('ALTER TABLE `'.$prefix.'users` ADD FOREIGN KEY (`referrer`)
				REFERENCES `'.$prefix.'users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;');
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
