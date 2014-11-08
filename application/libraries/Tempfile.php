<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries;

class Tempfile {
	private $file;

	public function __construct()
	{
		$this->file = tempnam(sys_get_temp_dir(), "tempfile");
	}

	public function __destruct()
	{
		if (file_exists($this->file)) {
			unlink($this->file);
		}
	}

	public function get_file()
	{
		return $this->file;
	}
}
