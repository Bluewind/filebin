<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_file_delete extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23203);
		$this->userCounter = 3100;
	}

	public function test_delete_canDeleteUploaded()
	{
		$apikey = $this->createUserAndApikey();
		$ret = $this->uploadFile($apikey, "data/tests/small-file");
		$id = $ret["data"]["ids"][0];

		$ret = $this->CallEndpoint("POST", "file/delete", array(
			"apikey" => $apikey,
			"ids[1]" => $id,
		));
		$this->expectSuccess("delete uploaded file", $ret);

		$this->t->ok(empty($ret["data"]["errors"]), "no errors");
		$this->t->is_deeply(array(
			$id => array(
				"id" => $id
			)
		), $ret["data"]["deleted"], "deleted wanted ID");
		$this->t->is($ret["data"]["total_count"], 1, "total_count correct");
		$this->t->is($ret["data"]["deleted_count"], 1, "deleted_count correct");
	}

	public function test_delete_errorIfNotOwner()
	{
		$apikey = $this->createUserAndApikey();
		$apikey2 = $this->createUserAndApikey();
		$ret = $this->uploadFile($apikey, "data/tests/small-file");
		$id = $ret["data"]["ids"][0];

		$ret = $this->CallEndpoint("POST", "file/delete", array(
			"apikey" => $apikey2,
			"ids[1]" => $id,
		));
		$this->expectSuccess("delete file of someone else", $ret);

		$this->t->ok(empty($ret["data"]["deleted"]), "not deleted");
		$this->t->is_deeply(array(
			$id => array(
				"id" => $id,
				"reason" => "wrong owner"
			)
		), $ret["data"]["errors"], "error wanted ID");
		$this->t->is($ret["data"]["total_count"], 1, "total_count correct");
		$this->t->is($ret["data"]["deleted_count"], 0, "deleted_count correct");
	}

}
