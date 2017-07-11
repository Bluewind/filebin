<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_change_filesize_type extends CI_Migration {

	public function up()
	{
		$prefix = $this->db->dbprefix;

		if ($this->db->dbdriver == 'postgre') {
			$this->db->query('
				ALTER TABLE "'.$prefix.'file_storage"
					ALTER "filesize" TYPE bigint;
				');
		} else {
			$this->db->query('
				ALTER TABLE `'.$prefix.'file_storage`
					MODIFY `filesize` bigint;
				');
		}

		$chunk = 500;

		$this->db->where('filesize', 2147483647);
		$total = $this->db->count_all_results("file_storage");

		for ($limit = 0; $limit < $total; $limit += $chunk) {
			$query = $this->db->select('hash, id')
				->from('file_storage')
				->where('filesize', 2147483647)
				->limit($chunk, $limit)
				->get()->result_array();

			foreach ($query as $key => $item) {
				$data_id = $item["hash"].'-'.$item['id'];
				$filesize = filesize($this->mfile->file($data_id));

				$this->db->where('id', $item['id'])
					->set(array(
						'filesize' => $filesize,
					))
					->update('file_storage');
			}
		}
	}

	public function down()
	{
		throw new \exceptions\ApiException("migration/downgrade-not-supported", "downgrade not supported");
	}
}
