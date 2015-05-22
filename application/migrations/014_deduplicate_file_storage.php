<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_deduplicate_file_storage extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			throw new \exceptions\ApiException("migration/postgres/not-implemented", "migration 14 not yet implemented for postgres");
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
				DELETE `'.$prefix.'file_storage`
				FROM `'.$prefix.'file_storage`
				LEFT OUTER JOIN `'.$prefix.'files` ON `'.$prefix.'files`.file_storage_id = `'.$prefix.'file_storage`.id
				WHERE `'.$prefix.'file_storage`.id NOT IN (
					SELECT min(x.id)
					FROM (
						SELECT fs.id, fs.hash
						FROM `'.$prefix.'file_storage` fs
					) x
					GROUP BY x.hash
				)
				AND `'.$prefix.'files`.id IS NULL
			');

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

			$this->db->query('
				ALTER TABLE `'.$prefix.'files`
				ADD FOREIGN KEY (`file_storage_id`) REFERENCES `'.$prefix.'file_storage`(`id`) ON DELETE CASCADE ON UPDATE CASCADE;
			');

			$this->dbforge->drop_column("files", "hash");
			$this->dbforge->drop_column("files", "mimetype");
			$this->dbforge->drop_column("files", "filesize");
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
