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

}

