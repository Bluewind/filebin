<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_libraries_tempfile extends \test\Test {

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

	public function test_destructor_normalCase()
	{
		$t = new \libraries\Tempfile();
		$file = $t->get_file();
		$this->t->is(file_exists($file), true, "file should exist");
		unset($t);
		$this->t->is(file_exists($file), false, "file should no longer exist after destruction of object");
	}

	public function test_destructor_alreadyRemoved()
	{
		$t = new \libraries\Tempfile();
		$file = $t->get_file();
		$this->t->is(file_exists($file), true, "file should exist");
		unlink($file);
		$this->t->is(file_exists($file), false, "file deleted");
		unset($t);
		$this->t->is(file_exists($file), false, "file should no longer exist after destruction of object");
	}
}
