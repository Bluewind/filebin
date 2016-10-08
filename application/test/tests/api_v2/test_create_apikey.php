<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_create_apikey extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23202);
		$this->userCounter = 2100;
	}

	public function test_create_apikey_createNewKey()
	{
		$this->createUser(1);
		$ret = $this->CallEndpoint("POST", "user/create_apikey", array(
			"username" => "apiv2testuser1",
			"password" => "testpass1",
			"access_level" => "apikey",
			"comment" => "main api key",
		));
		$this->expectSuccess("create-apikey", $ret);

		$this->t->isnt($ret["data"]["new_key"], "", "apikey not empty");
	}

	public function test_authentication_invalidPassword()
	{
		$userid = $this->createUser(3);
		$ret = $this->CallEndpoint("POST", "user/create_apikey", array(
			"username" => "apiv2testuser3",
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
		$ret = $this->CallEndpoint("POST", "user/create_apikey", array(
			"username" => "apiv2testuserinvalid",
			"password" => "testpass4",
		));
		$this->expectError("invalid username", $ret);

		$this->t->is_deeply(array (
			'status' => 'error',
			'error_id' => 'user/login-failed',
			'message' => 'Login failed',
		), $ret, "expected error");
	}
}
