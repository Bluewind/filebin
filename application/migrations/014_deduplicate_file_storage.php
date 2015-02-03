<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_deduplicate_file_storage extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		// FIXME: use prefix

		if ($this->db->dbdriver == 'postgre') {
			throw new \exceptions\ApiException("migration/postgres/not-implemented", "migration 14 not implemented yet for postgres");
		} else {
			$this->db->query('
				CREATE TABLE `file_storage` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`filesize` int(11) NOT NULL,
					`mimetype` varchar(255) NOT NULL,
					`hash` char(32) NOT NULL,
					`hash_collision_counter` int(11) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `data_id` (`hash`, `hash_collision_counter`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			');
			$this->db->query('
				ALTER TABLE `files`
				ADD `file_storage_id` INT NOT NULL,
				ADD INDEX (`file_storage_id`),
				ADD FOREIGN KEY (`file_storage_id`) REFERENCES `filebin_test`.`file_storage`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
			');

			$this->db->query('
				INSERT INTO file_storage (storage-id, filesize, mimetype)
				SELECT hash, filesize, mimetype FROM files;
			');

			$this->db->query('
				UPDATE files f
				JOIN file_storage fs ON fs.data_id = f.hash
				SET f.file_storage_id = fs.id
			');

			$this->dbforge->drop_column("files", array("hash", "mimetype", "filesize"));
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
