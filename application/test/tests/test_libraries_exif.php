<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_libraries_exif extends \test\Test {

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

	public function test_get_exif_jpeg()
	{
		$ret = \libraries\Exif::get_exif(FCPATH.'/data/tests/exif-orientation-examples/Portrait_1.jpg');

		$this->t->is($ret['Orientation'], 1, "Get correct EXIF Orientation");
		$this->t->is($ret['FileName'], "Portrait_1.jpg", "Get correct EXIF FileName");
	}

	public function test_get_exif_invalidTypes()
	{
		$ret = \libraries\Exif::get_exif(FCPATH.'/data/tests/black_white.png');
		$this->t->is($ret, false, "PNG not supported");
	}

	public function test_get_exif_missingFile()
	{
		$ret = \libraries\Exif::get_exif(FCPATH.'/data/tests/thisFileDoesNotExist');
		$this->t->is($ret, false, "Should return false for missing file");
	}

}

