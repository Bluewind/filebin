<?php
/*
 * Copyright 2014-2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries\Image;

interface ImageDriver {
	/**
	 * Replace the current image by reading in a file
	 * @param file file to read
	 */
	public function read($file);

	/**
	 * Return the current image rendered to a specific format. Passing null as
	 * the target_type returns the image in the format of the source image
	 * (loaded with read()).
	 *
	 * @param target_type one of IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG or null
	 * @return binary image data
	 */
	public function get($target_type);

	/**
	 * Resize the image.
	 * @param width
	 * @param height
	 */
	public function resize($width, $height);

	/**
	 * Crop the image to the area defined by x, y, width and height.
	 *
	 * @param x starting coordinate
	 * @param y starting coordinate
	 * @param width width of the area
	 * @param height height of the area
	 */
	public function crop($x, $y, $width, $height);

	/**
	 * Crop/resize the image to fit into the desired size. This also rotates
	 * the image if the source image had an EXIF orientation tag.
	 *
	 * @param width width of the resulting image
	 * @param height height of the resulting image
	 */
	public function makeThumb($target_width, $target_height);

	/**
	 * Rotate the image according to the sources EXIF orientation tag if any.
	 */
	public function apply_exif_orientation();

	/**
	 * Mirror the image along the x axis.
	 */
	public function mirror();

	/**
	 * Return priority for this driver. Higher number means a higher priority.
	 *
	 * @param mimetype mimetype of the file
	 * @return > 0 if supported, < 0 if the mimetype can't be handled
	 */
	public static function get_priority($mimetype);
}

namespace libraries;

/**
 * This class deals with a single image and provides useful operations that
 * operate on this image.
 */
class Image {
	private $driver;

	private $image_drivers = array(
		"libraries\Image\Drivers\GD",
		"libraries\Image\Drivers\imagemagick",
	);

	/**
	 * Create a new object and load the contents of file.
	 * @param file file to read
	 */
	public function __construct($file)
	{
		$this->read($file);
	}

	/**
	 * Get the best driver supporting $mimetype.
	 *
	 * @param drivers list of driver classes
	 * @param mimetype mimetype the driver should support
	 * @return driver from $drivers or NULL if no driver supports the type
	 */
	private function best_driver($drivers, $mimetype)
	{
		$best = 0;
		$best_driver = null;
		foreach ($drivers as $driver) {
			$current = $driver::get_priority($mimetype);
			if ($best == 0 || ($current > $best && $current > 0)) {
				$best_driver = $driver;
				$best = $current;
			}
		}

		return $best_driver;
	}

	/**
	 * Replace the current image by reading in a file
	 * @param file file to read
	 */
	public function read($file)
	{
		$mimetype = mimetype($file);

		$driver = $this->best_driver($this->image_drivers, $mimetype);

		if ($driver === NULL) {
			throw new \exceptions\ApiException("libraries/Image/unsupported-image-type", "Unsupported image type");
		}

		$this->driver = new $driver($file);
	}

	/**
	 * Return the current image rendered to a specific format. Passing null as
	 * the target_type returns the image in the format of the source image
	 * (loaded with read()).
	 *
	 * @param target_type one of IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG or null
	 * @return binary image data
	 */
	public function get($target_type = null)
	{
		return $this->driver->get($target_type);
	}

	/**
	 * Resize the image.
	 * @param width
	 * @param height
	 */
	public function resize($width, $height)
	{
		return $this->driver->resize($width, $height);
	}

	/**
	 * Crop the image to the area defined by x, y, width and height.
	 *
	 * @param x starting coordinate
	 * @param y starting coordinate
	 * @param width width of the area
	 * @param height height of the area
	 */
	public function crop($x, $y, $width, $height)
	{
		return $this->driver->crop($x, $y, $width, $height);
	}

	/**
	 * Crop/resize the image to fit into the desired size. This also rotates
	 * the image if the source image had an EXIF orientation tag.
	 *
	 * @param width width of the resulting image
	 * @param height height of the resulting image
	 */
	public function makeThumb($target_width, $target_height)
	{
		return $this->driver->makeThumb($target_width, $target_height);
	}

	static public function get_exif_orientation($file)
	{
		$exif = \libraries\Exif::get_exif($file);
		if (isset($exif["Orientation"])) {
			return $exif["Orientation"];
		}
		return 0;
	}

	/**
	 * Rotate the image according to the sources EXIF orientation tag if any.
	 */
	public function apply_exif_orientation()
	{
		return $this->driver->apply_exif_orientation();
	}

	/**
	 * Mirror the image along the x axis.
	 */
	public function mirror()
	{
		return $this->driver->mirror();
	}

}
