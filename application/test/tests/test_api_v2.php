<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_api_v2 extends \test\Test {

	public function __construct()
	{
		parent::__construct();

		$CI =& get_instance();
		$CI->load->model("muser");
		$CI->load->model("mfile");

	}

	private function uploadFile($apikey, $file)
	{
		$ret = $this->CallAPI("POST", "$this->server/api/v2.0.0/file/upload", array(
			"apikey" => $apikey,
			"file[1]" => curl_file_create($file),
		));
		$this->expectSuccess("upload file", $ret);
		return $ret;
	}

	private function createUser($counter)
	{
		$CI =& get_instance();
		$CI->db->insert("users", array(
			'username' => "testuser-api_v2-$counter",
			'password' => $CI->muser->hash_password("testpass$counter"),
			'email'    => "testuser$counter@localhost.invalid",
			'referrer' => NULL
		));

		return $CI->db->insert_id();
	}

	private function createApikey($userid, $access_level = "apikey")
	{
		return \service\user::create_apikey($userid, "", $access_level);
	}

	private function createUserAndApikey($access_level = "apikey")
	{
		static $counter = 100;
		$counter++;
		$userid = $this->createUser($counter);
		return $this->createApikey($userid, $access_level);
	}

	private function callEndpoint($verb, $endpoint, $data)
	{
		return $this->CallAPI($verb, "$this->server/api/v2.0.0/$endpoint", $data);
	}

	public function test_callPrivateEndpointsWithoutApikey()
	{
		$endpoints = array(
			"file/upload",
			"file/history",
			"file/delete",
			"file/create_multipaste",
			"user/apikeys",
			"user/create_apikey",
			"user/delete_apikey",
		);
		foreach ($endpoints as $endpoint) {
			$ret = $this->CallEndpoint("POST", $endpoint, array(
			));
			$this->expectError("call $endpoint without apikey", $ret);
			$this->t->is_deeply(array(
				'status' => 'error',
				'error_id' => 'api/not-authenticated',
				'message' => 'Not authenticated. FileBin requires you to have an account, please go to the homepage for more information.',
			   ), $ret, "expected error");
		}
	}

	public function test_callEndpointsWithoutEnoughPermissions()
	{
		$testconfig = array(
			array(
				"apikey" => $this->createUserAndApikey('basic'),
				"endpoints" => array(
					"file/delete",
					"file/history",
				),
			),
			array(
				"apikey" => $this->createUserAndApikey(),
				"endpoints" => array(
					"user/apikeys",
					"user/create_apikey",
					"user/delete_apikey",
				),
			),
		);
		foreach ($testconfig as $test) {
			foreach ($test['endpoints'] as $endpoint) {
				$ret = $this->CallEndpoint("POST", $endpoint, array(
					"apikey" => $test['apikey'],
				));
				$this->expectError("call $endpoint without enough permissions", $ret);
				$this->t->is_deeply(array(
					'status' => "error",
					'error_id' => "api/insufficient-permissions",
					'message' => "Access denied: Access level too low",
				   ), $ret, "expected permission error");
			}
		}
	}

	public function test_create_apikey_createNewKey()
	{
		$this->createUser(1);
		$ret = $this->CallEndpoint("POST", "user/create_apikey", array(
			"username" => "testuser-api_v2-1",
			"password" => "testpass1",
			"access_level" => "apikey",
			"comment" => "main api key",
		));
		$this->expectSuccess("create-apikey", $ret);

		$this->t->isnt($ret["data"]["new_key"], "", "apikey not empty");
	}

	public function test_apikeys_getApikey()
	{
		$userid = $this->createUser(2);
		$apikey = $this->createApikey($userid);
		$ret = $this->CallEndpoint("POST", "user/apikeys", array(
			"username" => "testuser-api_v2-2",
			"password" => "testpass2",
		));
		$this->expectSuccess("get apikeys", $ret);

		$this->t->is($ret["data"]["apikeys"][$apikey]["key"], $apikey, "expected key 1");
		$this->t->is($ret["data"]["apikeys"][$apikey]["access_level"], "apikey", "expected key 1 acces_level");
		$this->t->is($ret["data"]["apikeys"][$apikey]["comment"], "", "expected key 1 comment");
		$this->t->ok(is_int($ret["data"]["apikeys"][$apikey]["created"]) , "expected key 1 creation time is int");
	}

	public function test_delete_apikey_deleteOwnKey()
	{
		$apikey = $this->createUserAndApikey("full");
		$ret = $this->CallEndpoint("POST", "user/delete_apikey", array(
			"apikey" => $apikey,
			"delete_key" => $apikey,
		));
		$this->expectSuccess("delete apikey", $ret);

		$this->t->is($ret["data"]["deleted_keys"][$apikey]["key"], $apikey, "expected key");
	}

	public function test_delete_apikey_errorDeleteOtherUserKey()
	{
		$apikey = $this->createUserAndApikey("full");
		$apikey2 = $this->createUserAndApikey("full");
		$ret = $this->CallEndpoint("POST", "user/delete_apikey", array(
			"apikey" => $apikey,
			"delete_key" => $apikey2,
		));
		$this->expectError("delete apikey of other user", $ret);
		$this->t->is_deeply(array(
			'status' => 'error',
			'error_id' => 'user/delete_apikey/failed',
			'message' => 'Apikey deletion failed. Possibly wrong owner.',
		), $ret, "expected error");
	}

	public function test_authentication_invalidPassword()
	{
		$userid = $this->createUser(3);
		$ret = $this->CallEndpoint("POST", "user/apikeys", array(
			"username" => "testuser-api_v2-3",
			"password" => "wrongpass",
		));
		$this->expectError("invalid password", $ret);

		$this->t->is_deeply(array (
			'status' => 'error',
			'error_id' => 'user/login-failed',
			'message' => 'Login failed',
		), $ret, "expected error");
	}

	public function test_authentication_invalidUser()
	{
		$userid = $this->createUser(4);
		$ret = $this->CallEndpoint("POST", "user/apikeys", array(
			"username" => "testuser-api_v2-invalid",
			"password" => "testpass4",
		));
		$this->expectError("invalid username", $ret);

		$this->t->is_deeply(array (
			'status' => 'error',
			'error_id' => 'user/login-failed',
			'message' => 'Login failed',
		), $ret, "expected error");
	}

	public function test_history_empty()
	{
		$apikey = $this->createUserAndApikey();
		$ret = $this->CallEndpoint("POST", "file/history", array(
			"apikey" => $apikey,
		));
		$this->expectSuccess("get history", $ret);

		$this->t->ok(empty($ret["data"]["items"]), "items key exists and empty");
		$this->t->ok(empty($ret["data"]["multipaste_items"]), "multipaste_items key exists and empty");
		$this->t->is($ret["data"]["total_size"], 0, "total_size = 0 since no uploads");
	}

	public function test_get_config()
	{
		$ret = $this->CallEndpoint("GET", "file/get_config", array(
		));
		$this->expectSuccess("get_config", $ret);

		$this->t->like($ret["data"]["upload_max_size"], '/[0-9]+/', "upload_max_size is int");
		$this->t->like($ret["data"]["max_files_per_request"], '/[0-9]+/', "max_files_per_request is int");
	}

	public function test_upload_uploadFile()
	{
		$apikey = $this->createUserAndApikey();
		$ret = $this->CallEndpoint("POST", "file/upload", array(
			"apikey" => $apikey,
			"file[1]" => curl_file_create("data/tests/small-file"),
		));
		$this->expectSuccess("upload file", $ret);

		$this->t->ok(!empty($ret["data"]["ids"]), "got IDs");
		$this->t->ok(!empty($ret["data"]["urls"]), "got URLs");
	}

	public function test_upload_uploadFileSameMD5()
	{
		$apikey = $this->createUserAndApikey();
		$ret = $this->CallEndpoint("POST", "file/upload", array(
			"apikey" => $apikey,
			"file[1]" => curl_file_create("data/tests/message1.bin"),
			"file[2]" => curl_file_create("data/tests/message2.bin"),
		));
		$this->expectSuccess("upload file", $ret);

		$this->t->ok(!empty($ret["data"]["ids"]), "got IDs");
		$this->t->ok(!empty($ret["data"]["urls"]), "got URLs");

		foreach ($ret["data"]["urls"] as $url) {
			$data[] = $this->SendHTTPRequest("GET", $url, '');
		}
		$this->t->ok($data[0] !== $data[1], 'Returned file contents should differ');
	}

	public function test_upload_uploadNothing()
	{
		$apikey = $this->createUserAndApikey();
		$ret = $this->CallEndpoint("POST", "file/upload", array(
			"apikey" => $apikey,
		));
		$this->expectError("upload no file", $ret);
		$this->t->is_deeply(array(
			'status' => 'error',
			'error_id' => 'file/no-file',
			'message' => 'No file was uploaded or unknown error occurred.',
		), $ret, "expected reply");
	}

	public function test_history_notEmptyAfterUploadSameMD5()
	{
		$apikey = $this->createUserAndApikey();
		$this->CallEndpoint("POST", "file/upload", array(
			"apikey" => $apikey,
			"file[1]" => curl_file_create("data/tests/message1.bin"),
			"file[2]" => curl_file_create("data/tests/message2.bin"),
		));
		$expected_filesize = filesize("data/tests/message1.bin") + filesize("data/tests/message2.bin");

		$ret = $this->CallEndpoint("POST", "file/history", array(
			"apikey" => $apikey,
		));
		$this->expectSuccess("history not empty after upload", $ret);

		$this->t->ok(!empty($ret["data"]["items"]), "history not empty after upload (items)");
		$this->t->ok(empty($ret["data"]["multipaste_items"]), "didn't upload multipaste");
		$this->t->is($ret["data"]["total_size"], $expected_filesize, "total_size == uploaded files");
	}

	public function test_history_notEmptyAfterMultipaste()
	{
		$apikey = $this->createUserAndApikey();
		$uploadid = $this->uploadFile($apikey, "data/tests/small-file")['data']['ids'][0];
		$multipasteid = $this->CallEndpoint("POST", "file/create_multipaste", array(
			"apikey" => $apikey,
			'ids[1]' => $uploadid,
		))['data']['url_id'];

		$ret = $this->CallEndpoint("POST", "file/history", array(
			"apikey" => $apikey,
		));
		$this->expectSuccess("history not empty after multipaste", $ret);

		$this->t->ok(!empty($ret["data"]["items"]), "history not empty after multipaste (items)");
		$this->t->is($ret['data']["multipaste_items"][$multipasteid]['items'][$uploadid]['id'], $uploadid, "multipaste contains correct id");
		$this->t->is_deeply(array(
			'url_id', 'date', 'items'
		), array_keys($ret['data']["multipaste_items"][$multipasteid]), "multipaste info only lists correct keys");
		$this->t->is_deeply(array('id'), array_keys($ret['data']["multipaste_items"][$multipasteid]['items'][$uploadid]), "multipaste item info only lists correct keys");
	}

	public function test_history_notEmptyAfterUpload()
	{
		$apikey = $this->createUserAndApikey();
		$uploadid = $this->uploadFile($apikey, "data/tests/small-file")['data']['ids'][0];
		$uploadid_image = $this->uploadFile($apikey, "data/tests/black_white.png")['data']['ids'][0];
		$expected_size = filesize("data/tests/small-file") + filesize("data/tests/black_white.png");

		$ret = $this->CallEndpoint("POST", "file/history", array(
			"apikey" => $apikey,
		));
		$this->expectSuccess("history not empty after upload", $ret);

		$this->t->ok(!empty($ret["data"]["items"]), "history not empty after upload (items)");
		$this->t->is_deeply(array(
			'id', 'filename', 'mimetype', 'date', 'hash', 'filesize'
		), array_keys($ret['data']["items"][$uploadid]), "item info only lists correct keys");
		$this->t->is_deeply(array(
			'id', 'filename', 'mimetype', 'date', 'hash', 'filesize', 'thumbnail'
		), array_keys($ret['data']["items"][$uploadid_image]), "item info for image lists thumbnail too");
		$this->t->ok(empty($ret["data"]["multipaste_items"]), "didn't upload multipaste");
		$this->t->is($ret["data"]["total_size"], $expected_size, "total_size == uploaded files");
	}

	public function test_history_notSharedBetweenUsers()
	{
		$apikey = $this->createUserAndApikey();
		$apikey2 = $this->createUserAndApikey();
		$this->uploadFile($apikey, "data/tests/small-file");

		$ret = $this->CallEndpoint("POST", "file/history", array(
			"apikey" => $apikey2,
		));
		$this->expectSuccess("get history", $ret);

		$this->t->ok(empty($ret["data"]["items"]), "items key exists and empty");
		$this->t->ok(empty($ret["data"]["multipaste_items"]), "multipaste_items key exists and empty");
		$this->t->is($ret["data"]["total_size"], 0, "total_size = 0 since no uploads");
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
}
