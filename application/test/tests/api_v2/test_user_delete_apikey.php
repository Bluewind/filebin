<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_user_delete_apikey extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23206);
		$this->userCounter = 6100;
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

}
