<?php
/*
 * Copyright 2014-2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries;

class Exif {
	static public function get_exif($file)
	{
		// TODO: support more types (identify or exiftool? might be slow :( )
		try {
			$type = getimagesize($file)[2];
		} catch (\ErrorException $e) {
			return false;
		}
		switch ($type) {
		case IMAGETYPE_JPEG:
			getimagesize($file, $info);
			if (isset($info["APP1"]) && strpos($info["APP1"], "http://ns.adobe.com/xap/1.0/") === 0) {
				// ignore XMP data which makes exif_read_data throw a warning
				// http://stackoverflow.com/a/8864064
				return false;
			}
			return @exif_read_data($file);
			break;
		default:
		}

		return false;
	}

}
