<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_actually_deduplicate_file_storage extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			// no need for this query since 14 is not buggy
		} else {
			// XXX: This query also exists in migration 14
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

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
