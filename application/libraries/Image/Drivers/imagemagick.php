<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries\Image\Drivers;

class imagemagick implements \libraries\Image\ImageDriver {
	private $source_file;
	private $arguments = array();

	public static function get_priority($mimetype)
	{
		$mimetype = $mimetype;
		$base = explode("/", $mimetype)[0];

		if ($base == "image"
			|| in_array($mimetype, array("application/pdf"))) {
			return 100;
		}

		return -1;
	}

	/**
	 * Create a new object and load the contents of file.
	 * @param file file to read
	 */
	public function __construct($file)
	{
		$this->read($file);
	}

	public function read($file)
	{
		if (!file_exists($file)) {
			throw new \exceptions\ApiException("libraries/Image/drivers/imagemagick/missing-file", "Source file doesn't exist");
		}

		$this->source_file = $file;
		$this->arguments = array();
	}

	public function get($target_type = null)
	{
		if ($target_type === null) {
			return file_get_contents($this->source_file);
		}

		$command = array("convert");
		$command = array_merge($command, $this->arguments);
		$command[] = $this->source_file."[0]";

		switch ($target_type) {
			case IMAGETYPE_GIF:
				$command[] = "gif:-";
				break;
			case IMAGETYPE_JPEG:
				$command[] = "jpeg:-";
				break;
			case IMAGETYPE_PNG:
				$command[] = "png:-";
				break;
			default:
				assert(0);
		}

		try {
			$ret = (new \libraries\ProcRunner($command))->execSafe();
		} catch (\exceptions\ApiException $e) {
			throw new \exceptions\ApiException("libraries/Image/thumbnail-creation-failed", "Failed to create thumbnail", null, $e);
		}

		return $ret["stdout"];
	}

	public function resize($width, $height)
	{
		$this->arguments[] = "-resize";
		$this->arguments[] = "${width}x${height}";
	}

	public function crop($x, $y, $width, $height)
	{
		$this->arguments[] = "+repage";
		$this->arguments[] = "-crop";
		$this->arguments[] = "${width}x${height}+${x}+${y}";
		$this->arguments[] = "+repage";
	}

	// Source: http://salman-w.blogspot.co.at/2009/04/crop-to-fit-image-using-aspphp.html
	public function makeThumb($target_width, $target_height)
	{
		assert(is_int($target_width));
		assert(is_int($target_height));

		$this->apply_exif_orientation();

		$this->arguments[] = "-thumbnail";
		$this->arguments[] = "${target_width}x${target_height}^";
		$this->arguments[] = "-gravity";
		$this->arguments[] = "center";
		$this->arguments[] = "-extent";
		$this->arguments[] = "${target_width}x${target_height}^";
	}

	public function apply_exif_orientation()
	{
		$this->arguments[] = "-auto-orient";
	}

	public function mirror()
	{
		$this->arguments[] = "-flip";
	}

}
