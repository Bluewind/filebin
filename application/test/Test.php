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
	protected $server_url = "";
	private $testid = "";
	private $server_proc = null;

	public function __construct()
	{
		$this->t = new TestMore();
		$this->t->plan("no_plan");
	}

	public function __destruct()
	{
		if ($this->server_proc) {
			proc_terminate($this->server_proc);
		}
	}

	public function startServer($port)
	{
		$url = "http://127.0.0.1:$port/index.php";

		$pipes = [];
		$descriptorspec = [
			0 => ['file', '/dev/null', 'r'],
			1 => STDOUT,
			2 => STDOUT,
		];

		$this->server_proc = proc_open("php -S 127.0.0.1:$port", $descriptorspec, $pipes);

		$this->wait_for_server($url);
		$this->server_url = $url;
	}

	private function wait_for_server($url)
	{
		while (!$this->url_is_reachable($url)) {
			echo "Waiting for server at $url to start...\n";
			usleep(10000);
		}
	}

	private function url_is_reachable($url)
	{
		$handle = curl_init($url);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		curl_exec($handle);
		$status = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);

		if ($status == 200) {
			return true;
		}

		return false;
	}

	public function setTestID($testid)
	{
		$this->testid = $testid;
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
			"X-Testsuite-Testname: API request from ".$this->testid,
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
