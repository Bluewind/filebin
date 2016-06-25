<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_service_files extends \test\Test {

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

	public function test_ellipsize()
	{
		$a1 = "abc";
		$a2 = "abc\nabc";
		$a3 = "abc\nabc\nabc";
		$a4 = "abc\nabc\nabc\nabc";

		$this->t->is(\service\files::ellipsize($a1, 1, strlen($a1)),
			$a1, "Trim 1 line to 1, no change");

		$this->t->is(\service\files::ellipsize($a3, 3, strlen($a3)),
			$a3, "Trim 3 lines to 3, no change");

		$this->t->is(\service\files::ellipsize($a3, 5, strlen($a3)),
			$a3, "Trim 3 lines to 5, no change");

		$this->t->is(\service\files::ellipsize($a2, 1, strlen($a2)),
			"$a1\n...", "Trim 2 lines to 1, drop one line");

		$this->t->is(\service\files::ellipsize($a3, 2, strlen($a3)),
			"$a2\n...", "Trim 3 lines to 2, drop one line");

		$this->t->is(\service\files::ellipsize($a4, 2, strlen($a4)),
			"$a2\n...", "Trim 4 lines to 2, drop 2 lines");

		$this->t->is(\service\files::ellipsize($a3, 3, strlen($a3) + 1),
			"$a2\n...", "Last line incomplete, drop one line");

		$this->t->is(\service\files::ellipsize($a1, 5, strlen($a1) + 1),
			"$a1 ...", "Single line incomplete, only add dots");
	}


}

