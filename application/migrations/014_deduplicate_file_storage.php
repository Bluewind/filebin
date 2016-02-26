<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_deduplicate_file_storage extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				CREATE TABLE "'.$prefix.'file_storage" (
					"id" serial NOT NULL,
					"filesize" integer NOT NULL,
					"mimetype" varchar(255) NOT NULL,
					"hash" char(32) NOT NULL,
					"date" integer NOT NULL,
					PRIMARY KEY ("id"),
					UNIQUE ("id", "hash")
				);
			');
			$this->db->query('
				ALTER TABLE "'.$prefix.'files"
				ADD "file_storage_id" integer NULL;
				CREATE INDEX "'.$prefix.'files_file_storage_id_idx" ON "'.$prefix.'files" ("file_storage_id");
			');

			$this->db->query('
				INSERT INTO "'.$prefix.'file_storage" (filesize, mimetype, hash, date)
				SELECT filesize, mimetype, hash, date FROM "'.$prefix.'files";
			');

			$this->db->query('
				UPDATE "'.$prefix.'files" f
				SET file_storage_id = fs.id
				FROM "'.$prefix.'file_storage" fs
				WHERE fs.hash = f.hash
			');

			// remove file_storage rows that are not referenced by files.id
			// AND that are duplicates when grouped by hash
			$this->db->query('
				DELETE
				FROM "'.$prefix.'file_storage" fs
				USING "'.$prefix.'file_storage" fs2
				WHERE fs.hash = fs2.hash
				AND fs.id > fs2.id
				AND fs.id NOT IN (
					SELECT file_storage_id
					FROM "'.$prefix.'files" f
				);
			');
		} else {
			$this->db->query('
				CREATE TABLE `'.$prefix.'file_storage` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`filesize` int(11) NOT NULL,
					`mimetype` varchar(255) NOT NULL,
					`hash` char(32) NOT NULL,
					`date` int(11) NOT NULL,
					PRIMARY KEY (`id`),
					UNIQUE KEY `data_id` (`id`, `hash`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8;
			');
			$this->db->query('
				ALTER TABLE `'.$prefix.'files`
				ADD `file_storage_id` INT NOT NULL,
				ADD INDEX (`file_storage_id`);
			');

			$this->db->query('
				INSERT INTO `'.$prefix.'file_storage` (id, filesize, mimetype, hash, date)
				SELECT NULL, filesize, mimetype, hash, date FROM `'.$prefix.'files`;
			');

			$this->db->query('
				UPDATE `'.$prefix.'files` f
				JOIN `'.$prefix.'file_storage` fs ON fs.hash = f.hash
				SET f.file_storage_id = fs.id
			');

			// XXX: This query also exists in migration 15
			$this->db->query('
				DELETE fs
				FROM `'.$prefix.'file_storage` fs, `'.$prefix.'file_storage` fs2
				WHERE fs.hash = fs2.hash
				AND fs.id > fs2.id
				AND fs.id NOT IN (
					SELECT file_storage_id
					FROM `'.$prefix.'files` f
				);
				');
		}

		$chunk = 500;
		$total = $this->db->count_all("file_storage");

		for ($limit = 0; $limit < $total; $limit += $chunk) {
			$query = $this->db->select('hash, id')
				->from('file_storage')
				->limit($chunk, $limit)
				->get()->result_array();

			foreach ($query as $key => $item) {
				$old = $this->mfile->file($item["hash"]);
				$data_id = $item["hash"].'-'.$item["id"];
				$new = $this->mfile->file($data_id);
				if (file_exists($old)) {
					rename($old, $new);
				} else {
					echo "Warning: no file found for $data_id. Skipping...\n";
				}
			}
		}

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'files"
				ADD FOREIGN KEY ("file_storage_id") REFERENCES "'.$prefix.'file_storage"("id") ON DELETE CASCADE ON UPDATE CASCADE;
			');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'files`
				ADD FOREIGN KEY (`file_storage_id`) REFERENCES `'.$prefix.'file_storage`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
			');
		}

		$this->dbforge->drop_column("files", "hash");
		$this->dbforge->drop_column("files", "mimetype");
		$this->dbforge->drop_column("files", "filesize");
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
