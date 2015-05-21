<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test;

require_once APPPATH."/third_party/test-more-php/Test-More-OO.php";

class TestMore extends \TestMore {
	private $TestNamePrefix = "";

	public function setTestNamePrefix($prefix) {
		$this->TestNamePrefix = $prefix;
	}

	public function ok ($Result = NULL, $TestName = NULL) {
		return parent::ok($Result, $this->TestNamePrefix.$TestName);
	}
}

abstract class Test {
	protected $t;
	protected $server = "";

	public function __construct()
	{
		$this->t = new TestMore();
		$this->t->plan("no_plan");
	}

	public function setServer($server)
	{
		$this->server = $server;
	}

	// Method: POST, PUT, GET etc
	// Data: array("param" => "value") ==> index.php?param=value
	// Source: http://stackoverflow.com/a/9802854/953022
	protected function CallAPI($method, $url, $data = false)
	{
		$result = $this->SendHTTPRequest($method, $url, $data);

		$json = json_decode($result, true);
		if ($json === NULL) {
			$this->t->fail("json decode");
			$this->diagReply($result);
		}

		return $json;
	}

	protected function SendHTTPRequest($method, $url, $data = false)
	{
		$curl = curl_init();

		switch ($method) {
		case "POST":
			curl_setopt($curl, CURLOPT_POST, 1);

			if ($data)
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			break;
		case "PUT":
			curl_setopt($curl, CURLOPT_PUT, 1);
			break;
		default:
			if ($data)
				$url = sprintf("%s?%s", $url, http_build_query($data));
		}

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			"Accept: application/json",
			"Expect: ",
		));

		$result = curl_exec($curl);

		curl_close($curl);
		return $result;
	}

	protected function excpectStatus($testname, $reply, $status)
	{
		if (!isset($reply["status"]) || $reply["status"] != $status) {
			$this->t->fail($testname);
			$this->diagReply($reply);
		} else {
			$this->t->pass($testname);
		}
		return $reply;
	}

	protected function expectSuccess($testname, $reply)
	{
		return $this->excpectStatus($testname, $reply, "success");
	}

	protected function expectError($testname, $reply)
	{
		return $this->excpectStatus($testname, $reply, "error");
	}

	protected function diagReply($reply)
	{
		$this->t->diag("Request got unexpected response:");
		$this->t->diag(var_export($reply, true));
	}

	public function init()
	{
	}

	public function cleanup()
	{
	}

	public function done_testing()
	{
		$this->t->done_testing();
	}

	public function setTestNamePrefix($prefix) {
		$this->t->setTestNamePrefix($prefix);
	}
}
