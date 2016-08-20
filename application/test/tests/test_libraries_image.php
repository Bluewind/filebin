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
				// @codeCoverageIgnoreStart
				throw $e;
				// @codeCoverageIgnoreEnd
			}
		}
	}

	public function test_get_exif_orientation()
	{
		$ret = \libraries\Image::get_exif_orientation(FCPATH."/data/tests/black_white.png");
		$this->t->is($ret, 0, "Got correct Orientation for image without orientation information");

		foreach ([1,2,3,4,5,6,7,8] as $orientation) {
			$ret = \libraries\Image::get_exif_orientation(FCPATH."/data/tests/exif-orientation-examples/Landscape_$orientation.jpg");
			$this->t->is($ret, $orientation, "Got correct Orientation for Landscape_$orientation.jpg");

			$ret = \libraries\Image::get_exif_orientation(FCPATH."/data/tests/exif-orientation-examples/Portrait_$orientation.jpg");
			$this->t->is($ret, $orientation, "Got correct Orientation for Portrait_$orientation.jpg");
		}
	}

	public function test_makeThumb_differentOrientation()
	{
		foreach ([1,2,3,4,5,6,7,8] as $orientation) {
			$img = new \libraries\Image(FCPATH."/data/tests/exif-orientation-examples/Landscape_$orientation.jpg");
			$img->makeThumb(100, 100);
			$thumb = $img->get();
			$this->t->ok($thumb != '', "Got thumbnail for Landscape_$orientation.jpg");

			$img = new \libraries\Image(FCPATH."/data/tests/exif-orientation-examples/Portrait_$orientation.jpg");
			$img->makeThumb(100, 100);
			$thumb = $img->get();
			$this->t->ok($thumb != '', "Got thumbnail for Portrait_$orientation.jpg");
		}
	}

}

