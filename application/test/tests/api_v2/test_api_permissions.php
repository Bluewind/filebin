<?php
/*
 * Copyright 2015-2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class test_api_permissions extends common {

	public function __construct()
	{
		parent::__construct();
		$this->startServer(23200);
		$this->userCounter = 100;
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
				'message' => 'Not authenticated. FileBin requires you to have an account, please go to the homepage at http://127.0.0.1:23200/ for more information.',
			   ), $ret, "expected error");
		}
	}

	public function test_callPrivateEndpointsWithUnsupportedAuthentication()
	{
		$endpoints = array(
			"file/upload",
			"file/history",
			"file/delete",
			"file/create_multipaste",
			"user/apikeys",
			// create_apikey is the only one that supports username/pw
			//"user/create_apikey",
			"user/delete_apikey",
		);
		foreach ($endpoints as $endpoint) {
			$ret = $this->CallEndpoint("POST", $endpoint, array(
				"username" => "apiv2testuser1",
				"password" => "testpass1",
			));
			$this->expectError("call $endpoint without apikey", $ret);
			$this->t->is_deeply(array(
				'status' => 'error',
				'error_id' => 'api/not-authenticated',
				'message' => 'Not authenticated. FileBin requires you to have an account, please go to the homepage at http://127.0.0.1:23200/ for more information.',
			   ), $ret, "expected error");
		}
	}

	public function test_callEndpointsWithoutEnoughPermissions()
	{
		$testconfig = array(
			array(
				"have_level" => "basic",
				"wanted_level" => "apikey",
				"apikey" => $this->createUserAndApikey('basic'),
				"endpoints" => array(
					"file/delete",
					"file/history",
				),
			),
			array(
				"have_level" => "apikey",
				"wanted_level" => "full",
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
					'message' => "Access denied: Access level too low. Required: ${test['wanted_level']}; Have: ${test['have_level']}",
				   ), $ret, "expected permission error");
			}
		}
	}

}
