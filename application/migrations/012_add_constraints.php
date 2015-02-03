<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_constraints extends CI_Migration {

	public function up()
	{
		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('ALTER TABLE "users" ALTER COLUMN "referrer" TYPE integer');
			$this->db->query('ALTER TABLE "users" ALTER COLUMN "referrer" DROP NOT NULL');
			$this->db->query('CREATE INDEX "users_referrer_idx" ON "users" ("referrer")');
			$this->db->query('UPDATE "users" SET "referrer" = NULL where "referrer" = 0');
			$this->db->query('
				ALTER TABLE "users"
					ADD CONSTRAINT "referrer_user_fkey" FOREIGN KEY ("referrer")
						REFERENCES "users"("id") ON DELETE RESTRICT ON UPDATE RESTRICT
			');

		} else {

			$this->db->query("ALTER TABLE `users` ADD INDEX(`referrer`);");
			$this->db->query("ALTER TABLE `users` CHANGE `referrer` `referrer`
				INT(8) UNSIGNED NULL;");
			$this->db->query("UPDATE `users` SET `referrer` = NULL where `referrer` = 0;");
			$this->db->query("ALTER TABLE `users` ADD FOREIGN KEY (`referrer`)
				REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;");
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
