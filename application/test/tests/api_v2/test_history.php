<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_history extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23201);
		$this->userCounter = 1100;
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
		$this->t->is($ret["data"]["total_size"], "0", "total_size = 0 since no uploads");
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
		$this->t->is($ret["data"]["total_size"], "$expected_filesize", "total_size == uploaded files");
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
		$this->t->is($ret["data"]["total_size"], "$expected_size", "total_size == uploaded files");
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
		$this->t->is($ret["data"]["total_size"], "0", "total_size = 0 since no uploads");
	}

	public function test_history_specialVarsNotExpanded()
	{
		$apikey = $this->createUserAndApikey();
		$uploadid = $this->uploadFile($apikey, "data/tests/{elapsed_time}.txt")['data']['ids'][0];

		$ret = $this->CallEndpoint("POST", "file/history", array(
			"apikey" => $apikey,
		));
		$this->expectSuccess("get history", $ret);

		$this->t->is($ret["data"]["items"][$uploadid]['filename'], '{elapsed_time}.txt', "{elapsed_time} is not expanded in history reply");
	}
}
