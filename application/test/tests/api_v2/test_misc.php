<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_misc extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23207);
		$this->userCounter = 7100;
	}

	public function test_apikeys_getApikey()
	{
		$userid = $this->createUser(2);
		$apikey = $this->createApikey($userid);
		$apikey_full = $this->createApikey($userid, "full");
		$ret = $this->CallEndpoint("POST", "user/apikeys", array(
			"apikey" => $apikey_full,
		));
		$this->expectSuccess("get apikeys", $ret);

		$this->t->is($ret["data"]["apikeys"][$apikey]["key"], $apikey, "expected key 1");
		$this->t->is($ret["data"]["apikeys"][$apikey]["access_level"], "apikey", "expected key 1 acces_level");
		$this->t->is($ret["data"]["apikeys"][$apikey]["comment"], "", "expected key 1 comment");
		$this->t->ok(is_int($ret["data"]["apikeys"][$apikey]["created"]) , "expected key 1 creation time is int");
	}

	public function test_get_config()
	{
		$ret = $this->CallEndpoint("GET", "file/get_config", array(
		));
		$this->expectSuccess("get_config", $ret);

		$this->t->like($ret["data"]["upload_max_size"], '/[0-9]+/', "upload_max_size is int");
		$this->t->like($ret["data"]["max_files_per_request"], '/[0-9]+/', "max_files_per_request is int");
	}


}
