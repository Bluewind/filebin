<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries;

/**
 * This class deals with a single image and provides useful operations that
 * operate on this image.
 */
class Image {
	private $img;
	private $source_type;
	private $exif;

	/**
	 * Create a new object and load the contents of file.
	 * @param file file to read
	 */
	public function __construct($file)
	{
		$this->read($file);
	}

	/**
	 * Replace the current image by reading in a file
	 * @param file file to read
	 */
	public function read($file)
	{
		$img = imagecreatefromstring(file_get_contents($file));
		if ($img === false) {
			show_error("Unsupported image type");
		}
		$this->set_img_object($img);
		$this->fix_alpha();

		$this->source_type = getimagesize($file)[2];
		$this->exif = self::get_exif($file);
	}

	static private function get_exif($file)
	{
		$type = getimagesize($file)[2];
		switch ($type) {
		case IMAGETYPE_JPEG:
			getimagesize($file, $info);
			if (isset($info["APP1"]) && strpos($info["APP1"], "http://ns.adobe.com/xap/1.0/") === 0) {
				// ignore XMP data which makes exif_read_data throw a warning
				// http://stackoverflow.com/a/8864064
				return false;
			}
			return exif_read_data($file);
			break;
		default:
		}

		return false;
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
		if ($target_type === null) {
			$target_type = $this->source_type;
		}

		ob_start();
		switch ($target_type) {
			case IMAGETYPE_GIF:
				$ret = imagegif($this->img);
				break;
			case IMAGETYPE_JPEG:
				$ret = imagejpeg($this->img);
				break;
			case IMAGETYPE_PNG:
				$ret = imagepng($this->img);
				break;
			default:
				assert(0);
		}
		$result = ob_get_clean();

		if (!$ret) {
			show_error("Failed to create thumbnail");
		}

		return $result;
	}

	/**
	 * Resize the image.
	 * @param width
	 * @param height
	 */
	public function resize($width, $height)
	{
		$temp_gdim = imagecreatetruecolor($width, $height);
		$this->fix_alpha();
		imagecopyresampled(
			$temp_gdim,
			$this->img,
			0, 0,
			0, 0,
			$width, $height,
			imagesx($this->img), imagesy($this->img)
		);

		$this->set_img_object($temp_gdim);
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
		$thumb = imagecreatetruecolor($width, $height);
		$this->fix_alpha();
		imagecopy(
			$thumb,
			$this->img,
			0, 0,
			$x, $y,
			$width, $height
		);

		$this->set_img_object($thumb);
	}

	/**
	 * Crop/resize the image to fit into the desired size. This also rotates
	 * the image if the source image had an EXIF orientation tag.
	 *
	 * @param width width of the resulting image
	 * @param height height of the resulting image
	 */
	// Source: http://salman-w.blogspot.co.at/2009/04/crop-to-fit-image-using-aspphp.html
	public function makeThumb($target_width, $target_height)
	{
		$source_aspect_ratio = imagesx($this->img) / imagesy($this->img);
		$desired_aspect_ratio = $target_width / $target_height;

		if ($source_aspect_ratio > $desired_aspect_ratio) {
			// Triggered when source image is wider
			$temp_height = $target_height;
			$temp_width = round(($target_height * $source_aspect_ratio));
		} else {
			// Triggered otherwise (i.e. source image is similar or taller)
			$temp_width = $target_width;
			$temp_height = round(($target_width / $source_aspect_ratio));
		}

		$this->resize($temp_width, $temp_height);

		$x0 = ($temp_width - $target_width) / 2;
		$y0 = ($temp_height - $target_height) / 2;
		$this->crop($x0, $y0, $target_width, $target_height);

		$this->apply_exif_orientation();
	}

	static public function get_exif_orientation($file)
	{
		$exif = self::get_exif($file);
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
		if (isset($this->exif['Orientation'])) {
			$mirror = false;
			$deg    = 0;

			switch ($this->exif['Orientation']) {
			case 2:
				$mirror = true;
				break;
			case 3:
				$deg = 180;
				break;
			case 4:
				$deg = 180;
				$mirror = true;
				break;
			case 5:
				$deg = 270;
				$mirror = true;
				break;
			case 6:
				$deg = 270;
				break;
			case 7:
				$deg = 90;
				$mirror = true;
				break;
			case 8:
				$deg = 90;
				break;
			}

			if ($deg) {
				$this->set_img_object(imagerotate($this->img, $deg, 0));
			}

			if ($mirror) {
				$this->mirror();
			}
		}
	}

	/**
	 * Mirror the image along the x axis.
	 */
	public function mirror()
	{
		$width = imagesx($this->img);
		$height = imagesy($this->img);

		$src_x = $width -1;
		$src_y = 0;
		$src_width = -$width;
		$src_height = $height;

		$imgdest = imagecreatetruecolor($width, $height);
		imagealphablending($imgdest,false);
		imagesavealpha($imgdest,true);

		imagecopyresampled($imgdest, $this->img, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height);
		$this->set_img_object($imgdest);
	}

	private function set_img_object($new)
	{
		assert($new !== false);

		$old = $this->img;
		$this->img = $new;

		if ($old != null) {
			imagedestroy($old);
		}
	}

	private function fix_alpha()
	{
		imagealphablending($this->img,false);
		imagesavealpha($this->img,true);
	}

}
