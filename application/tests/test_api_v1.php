<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace tests;

class test_api_v1 extends Test {

	private $apikeys = array();

	public function __construct()
	{
		parent::__construct();

		$CI =& get_instance();
		$CI->load->model("muser");
		$CI->load->model("mfile");

		foreach (array(1,2,3,4,5) as $i) {
			$CI->db->insert("users", array(
				'username' => "testuser-api_v1-$i",
				'password' => $CI->muser->hash_password("testpass$i"),
				'email'    => "testuser$i@localhost.invalid",
				'referrer' => NULL
			));
			$this->apikeys[$i] = \service\user::create_apikey($CI->db->insert_id(), "", "apikey");
		}

	}

	public function test_create_apikey_createNewKey()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/user/create_apikey", array(
			"username" => "testuser-api_v1-1",
			"password" => "testpass1",
			"access_level" => "apikey",
			"comment" => "main api key",
		));
		$this->expectSuccess("create-apikey", $ret);

		$this->t->isnt($ret["data"]["new_key"], "", "apikey not empty");
	}

	public function test_history_empty()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/history", array(
			"apikey" => $this->apikeys[1],
		));
		$this->expectSuccess("get history", $ret);

		$this->t->ok(empty($ret["data"]["items"]), "items key exists and empty");
		$this->t->ok(empty($ret["data"]["multipaste_items"]), "multipaste_items key exists and empty");
		$this->t->is($ret["data"]["total_size"], 0, "total_size = 0 since no uploads");
	}

	public function test_get_config()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/get_config", array(
		));
		$this->expectSuccess("get_config", $ret);

		$this->t->like($ret["data"]["upload_max_size"], '/[0-9]+/', "upload_max_size is int");
		$this->t->like($ret["data"]["max_files_per_request"], '/[0-9]+/', "max_files_per_request is int");
	}

	public function test_upload_uploadFile()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/upload", array(
			"apikey" => $this->apikeys[2],
			"file[1]" => curl_file_create("data/tests/small-file"),
		));
		$this->expectSuccess("upload file", $ret);

		$this->t->ok(!empty($ret["data"]["ids"]), "got IDs");
		$this->t->ok(!empty($ret["data"]["urls"]), "got URLs");
	}

	public function test_history_notEmptyAfterUpload()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/upload", array(
			"apikey" => $this->apikeys[3],
			"file[1]" => curl_file_create("data/tests/small-file"),
		));
		$this->expectSuccess("upload file", $ret);

		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/history", array(
			"apikey" => $this->apikeys[3],
		));
		$this->expectSuccess("history not empty after upload", $ret);

		$this->t->ok(!empty($ret["data"]["items"]), "history not empty after upload (items)");
		$this->t->ok(empty($ret["data"]["multipaste_items"]), "didn't upload multipaste");
		$this->t->is($ret["data"]["total_size"], filesize("data/tests/small-file"), "total_size == uploaded file");
	}

	public function test_history_notSharedBetweenUsers()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/upload", array(
			"apikey" => $this->apikeys[4],
			"file[1]" => curl_file_create("data/tests/small-file"),
		));
		$this->expectSuccess("upload file", $ret);

		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/history", array(
			"apikey" => $this->apikeys[5],
		));
		$this->expectSuccess("get history", $ret);

		$this->t->ok(empty($ret["data"]["items"]), "items key exists and empty");
		$this->t->ok(empty($ret["data"]["multipaste_items"]), "multipaste_items key exists and empty");
		$this->t->is($ret["data"]["total_size"], 0, "total_size = 0 since no uploads");
	}

	public function test_delete_canDeleteUploaded()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/upload", array(
			"apikey" => $this->apikeys[2],
			"file[1]" => curl_file_create("data/tests/small-file"),
		));
		$this->expectSuccess("upload file", $ret);

		$id = $ret["data"]["ids"][0];

		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/delete", array(
			"apikey" => $this->apikeys[2],
			"ids[1]" => $id,
		));
		$this->expectSuccess("delete uploaded file", $ret);

		$this->t->ok(empty($ret["data"]["errors"]), "no errors");
		$this->t->is_deeply(array($id => array("id" => $id)), $ret["data"]["deleted"], "deleted wanted ID");
		$this->t->is($ret["data"]["total_count"], 1, "total_count correct");
		$this->t->is($ret["data"]["deleted_count"], 1, "deleted_count correct");
	}

	public function test_delete_errorIfNotOwner()
	{
		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/upload", array(
			"apikey" => $this->apikeys[2],
			"file[1]" => curl_file_create("data/tests/small-file"),
		));
		$this->expectSuccess("upload file", $ret);

		$id = $ret["data"]["ids"][0];

		$ret = $this->CallAPI("POST", "$this->server/api/1.0.0/file/delete", array(
			"apikey" => $this->apikeys[1],
			"ids[1]" => $id,
		));
		$this->expectSuccess("delete file of someone else", $ret);

		$this->t->ok(empty($ret["data"]["deleted"]), "not deleted");
		$this->t->is_deeply(array($id => array("id" => $id, "reason" => "wrong owner")), $ret["data"]["errors"], "error wanted ID");
		$this->t->is($ret["data"]["total_count"], 1, "total_count correct");
		$this->t->is($ret["data"]["deleted_count"], 0, "deleted_count correct");
	}
}
