<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_add_constraints extends CI_Migration {

	public function up()
	{
		$this->db->query("ALTER TABLE `apikeys` ADD FOREIGN KEY (`user`)
			REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");
		$this->db->query("ALTER TABLE `files` ADD FOREIGN KEY (`user`)
			REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;");
		$this->db->query("ALTER TABLE `profiles` ADD FOREIGN KEY (`user`)
			REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");
		$this->db->query("ALTER TABLE `actions` ADD FOREIGN KEY (`user`)
			REFERENCES `users`(`id`) ON DELETE CASCADE ON UPDATE RESTRICT;");

		$this->db->query("ALTER TABLE `users` ADD INDEX(`referrer`);");
		$this->db->query("ALTER TABLE `users` CHANGE `referrer` `referrer`
			INT(8) UNSIGNED NULL;");
		$this->db->query("UPDATE `users` SET `referrer` = NULL where `referrer` = 0;");
		$this->db->query("ALTER TABLE `users` ADD FOREIGN KEY (`referrer`)
			REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;");
	}

	public function down()
	{
		show_error("downgrade not supported");
	}
}
