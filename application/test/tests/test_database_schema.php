<?php
/*
 * Copyright 2017 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_database_schema extends \test\Test {

	public function __construct()
	{
		parent::__construct();
	}

	public function test_file_storage_bigint() {
		$filesize = pow(2, 35) + 1;

		$CI =& get_instance();
		$CI->db->insert("file_storage", array(
			"filesize" => $filesize,
			"mimetype" => "text/plain",
			"hash" => md5("test"),
			"date" => time(),
		));
		$id = $CI->db->insert_id();
		$db_value = $CI->db->select('filesize')
			->from('file_storage')
			->where('id', $id)
			->get()->result_array()[0]["filesize"];
		$this->t->is(intval($db_value), $filesize, "Large filesize is stored correctly in db");
	}


}
