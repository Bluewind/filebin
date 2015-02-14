<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace tests;

class test_service_files extends Test {

	public function __construct()
	{
		parent::__construct();

		$CI =& get_instance();
		$CI->load->model("muser");
		$CI->load->model("mfile");

	}

	public function test_verify_uploaded_files_noFiles()
	{
		$a = array();
		try {
			\service\files::verify_uploaded_files($a);
			$this->t->fail("verify should error");
		} catch (\exceptions\UserInputException $e) {
			$this->t->is($e->get_error_id(), "file/no-file", "verify should error");
		}
	}

	public function test_verify_uploaded_files_normal()
	{
		$CI =& get_instance();
		$a = array(
			array(
				"name" => "foobar.txt",
				"type" => "text/plain",
				"tmp_name" => NULL,
				"error" => UPLOAD_ERR_OK,
				"size" => 1,
				"formfield" => "file[1]",
			)
		);

		\service\files::verify_uploaded_files($a);
		$this->t->pass("verify should work");
	}

	public function test_verify_uploaded_files_uploadError()
	{
		$CI =& get_instance();
		$a = array(
			array(
				"name" => "foobar.txt",
				"type" => "text/plain",
				"tmp_name" => NULL,
				"error" => UPLOAD_ERR_NO_FILE,
				"size" => 1,
				"formfield" => "file[1]",
			)
		);

		try {
			\service\files::verify_uploaded_files($a);
			$this->t->fail("verify should error");
		} catch (\exceptions\UserInputException $e) {
			$data = $e->get_data();
			$this->t->is($e->get_error_id(), "file/upload-verify", "verify should error");
			$this->t->is_deeply(array(
				'file[1]' => array(
					'filename' => 'foobar.txt',
					'formfield' => 'file[1]',
					'message' => 'No file was uploaded',
				),
			), $data, "expected data in exception");
		}
	}


}

