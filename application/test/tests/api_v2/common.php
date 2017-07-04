<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests\api_v2;

class common extends \test\Test {

	protected $userCounter = null;

	public function __construct()
	{
		parent::__construct();

		$CI =& get_instance();
		$CI->load->model("muser");
		$CI->load->model("mfile");
	}

	protected function uploadFile($apikey, $file)
	{
		$ret = $this->CallAPI("POST", "$this->server_url/api/v2.0.0/file/upload", array(
			"apikey" => $apikey,
			"file[1]" => curl_file_create($file),
		));
		$this->expectSuccess("upload file", $ret);
		return $ret;
	}

	protected function createUser($counter)
	{
		$CI =& get_instance();
		$CI->muser->add_user("apiv2testuser$counter", "testpass$counter",
			"testuser$counter@testsuite.local", NULL);
		return $CI->db->insert_id();
	}

	protected function createApikey($userid, $access_level = "apikey")
	{
		return \service\user::create_apikey($userid, "", $access_level);
	}

	protected function createUserAndApikey($access_level = "apikey")
	{
		assert($this->userCounter !== null);
		$this->userCounter++;
		$userid = $this->createUser($this->userCounter);
		return $this->createApikey($userid, $access_level);
	}

	protected function callEndpoint($verb, $endpoint, $data, $return_json = false)
	{
		return $this->CallAPI($verb, "$this->server_url/api/v2.0.0/$endpoint", $data, $return_json);
	}
}
