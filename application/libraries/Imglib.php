<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Imglib {
	/*
	 * This returns a square thumbnail for the input image
	 * Source: http://salman-w.blogspot.co.at/2009/04/crop-to-fit-image-using-aspphp.html
	 */
	public function makeThumb($file, $size = 150, $target_type = null)
	{
		$source_gdim = imagecreatefromstring(file_get_contents($file));
		if ($source_gdim === false) {
			show_error("Unsupported image type");
		}
		imagealphablending($source_gdim,false);
		imagesavealpha($source_gdim,true);

		list($source_width, $source_height, $source_type) = getimagesize($file);

		if ($target_type === null) {
			$target_type = $source_type;
		}

		$target_width = $size;
		$target_height = $size;

		$source_aspect_ratio = $source_width / $source_height;
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

		/*
		 * Resize the image into a temporary GD image
		 */

		$temp_gdim = imagecreatetruecolor($temp_width, $temp_height);
		imagealphablending($temp_gdim,false);
		imagesavealpha($temp_gdim,true);
		imagecopyresampled(
			$temp_gdim,
			$source_gdim,
			0, 0,
			0, 0,
			$temp_width, $temp_height,
			$source_width, $source_height
		);

		/*
		 * Copy cropped region from temporary image into the desired GD image
		 */

		$x0 = ($temp_width - $target_width) / 2;
		$y0 = ($temp_height - $target_height) / 2;
		$thumb = imagecreatetruecolor($target_width, $target_height);
		imagealphablending($thumb,false);
		imagesavealpha($thumb,true);
		imagecopy(
			$thumb,
			$temp_gdim,
			0, 0,
			$x0, $y0,
			$target_width, $target_height
		);

		/*
		 * Fix orientation according to exif tag
		 */
		try {
			$exif = exif_read_data($file);
		} catch (ErrorException $e) {
		}

		if (isset($exif['Orientation'])) {
			$mirror = false;
			$deg    = 0;

			switch ($exif['Orientation']) {
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
			if ($deg) $thumb = imagerotate($thumb, $deg, 0);
			if ($mirror) $thumb = $this->_mirrorImage($thumb);
		}

		ob_start();
		switch ($target_type) {
			case IMAGETYPE_GIF:
				$ret = imagegif($thumb);
				break;
			case IMAGETYPE_JPEG:
				$ret = imagejpeg($thumb);
				break;
			case IMAGETYPE_PNG:
				$ret = imagepng($thumb);
				break;
			default:
				assert(0);
		}
		$result = ob_get_clean();

		if (!$ret) {
			show_error("Failed to create thumbnail");
		}

		imagedestroy($thumb);
		imagedestroy($temp_gdim);
		imagedestroy($source_gdim);

		return $result;
	}

	private function _mirrorImage($imgsrc)
	{
		$width = imagesx($imgsrc);
		$height = imagesy($imgsrc);

		$src_x = $width -1;
		$src_y = 0;
		$src_width = -$width;
		$src_height = $height;

		$imgdest = imagecreatetruecolor($width, $height);
		imagealphablending($imgdest,false);
		imagesavealpha($imgdest,true);

		if (imagecopyresampled($imgdest, $imgsrc, 0, 0, $src_x, $src_y, $width, $height, $src_width, $src_height)) {
			return $imgdest;
		}

		return $imgsrc;
	}

}
