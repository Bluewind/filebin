<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_libraries_image extends \test\Test {

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

	public function test_type_supported_normalCase()
	{
		$this->t->is(\libraries\Image::type_supported('image/png'), true, 'image/png should be supported');
		$this->t->is(\libraries\Image::type_supported('image/jpeg'), true, 'image/jpeg should be supported');
		$this->t->is(\libraries\Image::type_supported('application/pdf'), true, 'application/pdf should be supported');

		$this->t->is(\libraries\Image::type_supported('application/octet-stream'), false, 'application/octet-stream should not be supported');
		$this->t->is(\libraries\Image::type_supported('text/plain'), false, 'text/plain should not be supported');
	}

	public function test_makeThumb_PNG()
	{
		$img = new \libraries\Image(FCPATH."/data/tests/black_white.png");
		$img->makeThumb(150, 150);
		$thumb = $img->get(IMAGETYPE_PNG);

		$this->t->ok($thumb !== "", "Got thumbnail");
	}

	public function test_makeThumb_PDF()
	{
		$img = new \libraries\Image(FCPATH."/data/tests/simple.pdf");
		$img->makeThumb(150, 150);
		$thumb = $img->get(IMAGETYPE_JPEG);

		$this->t->ok($thumb !== "", "Got thumbnail");
	}

	public function test_makeThumb_binaryFile()
	{
		try {
			$img = new \libraries\Image(FCPATH."/data/tests/message1.bin");
		} catch (\exceptions\PublicApiException $e) {
			$correct_error = $e->get_error_id() == "libraries/Image/unsupported-image-type";
			$this->t->ok($correct_error, "Should get exception");
			if (!$correct_error) {
				throw $e;
			}
		}
	}
}

