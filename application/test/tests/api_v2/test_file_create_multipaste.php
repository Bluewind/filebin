<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_file_create_multipaste extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23204);
		$this->userCounter = 4100;
	}

	public function test_create_multipaste_canCreate()
	{
		$apikey = $this->createUserAndApikey("basic");
		$ret = $this->uploadFile($apikey, "data/tests/small-file");
		$id = $ret["data"]["ids"][0];

		$ret = $this->uploadFile($apikey, "data/tests/small-file");
		$id2 = $ret["data"]["ids"][0];

		$ret = $this->CallEndpoint("POST", "file/create_multipaste", array(
			"apikey" => $apikey,
			"ids[1]" => $id,
			"ids[2]" => $id2,
		));
		$this->expectSuccess("create multipaste", $ret);

		$this->t->isnt($ret["data"]["url_id"], "", "got a multipaste ID");
		$this->t->isnt($ret["data"]["url"], "", "got a multipaste URL");
	}

	public function test_create_multipaste_errorOnWrongID()
	{
		$apikey = $this->createUserAndApikey("basic");
		$ret = $this->uploadFile($apikey, "data/tests/small-file");
		$id = $ret["data"]["ids"][0];

		$id2 = $id."invalid";
		$ret = $this->CallEndpoint("POST", "file/create_multipaste", array(
			"apikey" => $apikey,
			"ids[1]" => $id,
			"ids[2]" => $id2,
		));
		$this->expectError("create multipaste with wrong ID", $ret);

		$this->t->is_deeply(array(
			'status' => 'error',
			'error_id' => 'file/create_multipaste/verify-failed',
			'message' => 'Failed to verify ID(s)',
			'data' =>
			array (
				$id2 =>
				array (
					'id' => $id2,
					'reason' => 'doesn\'t exist',
				),
			),
		), $ret, "expected error response");
	}

	public function test_create_multipaste_errorOnWrongOwner()
	{
		$apikey = $this->createUserAndApikey("basic");
		$apikey2 = $this->createUserAndApikey("basic");
		$ret = $this->uploadFile($apikey, "data/tests/small-file");
		$id = $ret["data"]["ids"][0];

		$ret = $this->CallEndpoint("POST", "file/create_multipaste", array(
			"apikey" => $apikey2,
			"ids[1]" => $id,
		));
		$this->expectError("create multipaste with wrong owner", $ret);

		$this->t->is_deeply(array(
			'status' => 'error',
			'error_id' => 'file/create_multipaste/verify-failed',
			'message' => 'Failed to verify ID(s)',
			'data' =>
			array (
				$id =>
				array (
					'id' => $id,
					'reason' => 'not owned by you',
				),
			),
		), $ret, "expected error response");
	}

	public function test_delete_canDeleteMultipaste()
	{
		$apikey = $this->createUserAndApikey();
		$ret = $this->uploadFile($apikey, "data/tests/small-file");
		$id = $ret["data"]["ids"][0];
		$ret = $this->CallEndpoint("POST", "file/create_multipaste", array(
			"apikey" => $apikey,
			"ids[1]" => $id,
		));
		$this->expectSuccess("create multipaste", $ret);

		$mid = $ret['data']['url_id'];
		$ret = $this->CallEndpoint("POST", "file/delete", array(
			"apikey" => $apikey,
			"ids[1]" => $mid,
		));
		$this->expectSuccess("delete uploaded file", $ret);

		$this->t->ok(empty($ret["data"]["errors"]), "no errors");
		$this->t->is_deeply(array(
			$mid => array(
				"id" => $mid
			)
		), $ret["data"]["deleted"], "deleted wanted ID");
		$this->t->is($ret["data"]["total_count"], 1, "total_count correct");
		$this->t->is($ret["data"]["deleted_count"], 1, "deleted_count correct");
	}
}
