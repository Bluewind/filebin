<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_actually_deduplicate_file_storage extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			// likely no need for this migration since 14 won't be buggy
			throw new \exceptions\ApiException("migration/postgres/not-implemented", "migration 15 not yet implemented for postgres");
		} else {
			// XXX: This query also exists in migration 14
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
			$consistent = true;

			for ($limit = 0; $limit < $total; $limit += $chunk) {
				$query = $this->db->select('hash, id')
					->from('file_storage')
					->limit($chunk, $limit)
					->get()->result_array();

				foreach ($query as $key => $item) {
					$data_id = $item["hash"].'-'.$item["id"];
					$file = $this->mfile->file($data_id);
					if (!file_exists($file)) {
						echo "Warning: no file found for $data_id\n";
						$consistent = false;
					}
				}
			}

			if (!$consistent) {
				echo "Your database is not consistent with your file system.\n";
				echo "Please report this as it is most likely a bug.\n";
			}
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
