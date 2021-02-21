<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_libraries_procrunner extends \test\Test {

	public function __construct()
	{
		parent::__construct();
	}

	public function init()
	{
	}

	public function cleanup()
	{
	}

	public function test_exec_true()
	{
		$p = new \libraries\ProcRunner(['true']);
		$ret = $p->exec();

		$this->t->is($ret['stderr'], '', 'stderr should be empty');
		$this->t->is($ret['stdout'], '', 'stdout should be empty');
		$this->t->is($ret['return_code'], 0, 'return code should be 0');
	}

	public function test_exec_false()
	{
		$p = new \libraries\ProcRunner(['false']);
		$ret = $p->exec();

		$this->t->is($ret['stderr'], '', 'stderr should be empty');
		$this->t->is($ret['stdout'], '', 'stdout should be empty');
		$this->t->is($ret['return_code'], 1, 'return code should be 1');
	}

	public function test_exec_nonexistent()
	{
		$p = new \libraries\ProcRunner(['thisCommandDoesNotExist']);
		$ret = $p->exec();

		if (PHP_MAJOR_VERSION >= 8) {
			$this->t->is($ret['stderr'], "sh: line 1: thisCommandDoesNotExist: command not found\n", 'stderr should be empty');
		} else {
			$this->t->is($ret['stderr'], "sh: thisCommandDoesNotExist: command not found\n", 'stderr should be empty');
		}
		$this->t->is($ret['stdout'], '', 'stdout should be empty');
		$this->t->is($ret['return_code'], 127, 'return code should be 127');
	}

	public function test_exec_tac()
	{

		$line1 = "this is the first line";
		$line2 = "and this is the second one";
		$input = "$line1\n$line2\n";
		$output = "$line2\n$line1\n";

		$p = new \libraries\ProcRunner(['tac']);
		$p->input($input);
		$ret = $p->exec();

		$this->t->is($ret['stderr'], '', 'stderr should be empty');
		$this->t->is($ret['stdout'], $output, 'stdout should be reversed');
		$this->t->is($ret['return_code'], 0, 'return code should be 0');
	}

	public function test_forbid_nonzero()
	{
		$p = new \libraries\ProcRunner(['false']);
		$p->forbid_nonzero();

		try {
			$p->exec();
			$this->t->ok(false, "this should have caused an an exception");
		} catch (\exceptions\ApiException $e) {
			$this->t->is($e->get_error_id(), 'procrunner/non-zero-exit', "correct exception triggered");
			$this->t->is_deeply($e->get_data(), [
				"'false'",
				null,
				[
					'return_code' => 1,
					'stdout' => '',
					'stderr' => '',
				],
			], "correct exception data");
		}
	}

	public function test_forbid_stderr()
	{
		$p = new \libraries\ProcRunner(['bash', '-c', 'echo "This is a test error message" >&2; exit 2;']);
		$p->forbid_stderr();

		try {
			$p->exec();
			$this->t->ok(false, "this should have caused an an exception");
		} catch (\exceptions\ApiException $e) {
			$this->t->is($e->get_error_id(), 'procrunner/stderr', "correct exception triggered");
			$this->t->is_deeply($e->get_data(), [
				"'bash' '-c' 'echo \"This is a test error message\" >&2; exit 2;'",
				null,
				[
					'return_code' => 2,
					'stdout' => '',
					'stderr' => "This is a test error message\n",
				],
			], "correct exception data");
		}
	}
}

