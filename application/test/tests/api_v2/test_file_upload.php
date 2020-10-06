<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_file_upload extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23205);
		$this->userCounter = 5100;
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
			# remove tailing /
			$url = substr($url, 0, strlen($url) - 1);
			$data[] = $this->SendHTTPRequest("GET", $url, '');
		}
		$this->t->ok($data[0] !== $data[1], 'Returned file contents should differ');
		$this->t->is($data[0], file_get_contents("data/tests/message1.bin"), "Returned correct data for file 1");
		$this->t->is($data[1], file_get_contents("data/tests/message2.bin"), "Returned correct data for file 2");
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

	public function test_upload_minidlength()
	{
		$apikey = $this->createUserAndApikey();
		$ret = $this->CallEndpoint("POST", "file/upload", array(
			"apikey" => $apikey,
			"file[1]" => curl_file_create("data/tests/small-file"),
			"minimum-id-length" => 42,
		));
		$this->expectSuccess("upload file", $ret);

		foreach ($ret["data"]["urls"] as $url) {
			$matches = array();
			preg_match('/\/([^\/]+)\/$/', $url, $matches);
			$this->t->ok(strlen($matches[1]) >= 42, "minimum url length upheld");
		}
	}

	public function test_upload_bad_minidlength()
	{
		$apikey = $this->createUserAndApikey();

		$combinations = [
			"non-numberic minimum-id-length" => "nonumber",
			"negative minimum-id-length (-42)" => -42,
			"minimum-id-length=0" => 0,
			"minimum-id-length=1" => 1,
		];
		foreach ($combinations as $msg => $input) {
			$ret = $this->CallEndpoint("POST", "file/upload", array(
				"apikey" => $apikey,
				"file[1]" => curl_file_create("data/tests/small-file"),
				"minimum-id-length" => $input,
			));
			$this->expectError("upload file with bad minimum-id-length. Test value: $msg", $ret);
			$this->t->is_deeply(array(
				'status' => 'error',
				'error_id' => 'file/bad-minimum-id-length',
				'message' => "Passed parameter 'minimum-id-length' is not a valid integer or too small (min value: 2)",
			), $ret, "expected reply");
		}
	}
}
