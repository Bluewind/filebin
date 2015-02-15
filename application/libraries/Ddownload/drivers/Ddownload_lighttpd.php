<?php
/*
 * Copyright 2013 Pierre Schmitz <pierre@archlinux.de>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Ddownload_lighttpd extends Ddownload_Driver {

	public function serveFile($file, $filename, $type)
	{
		$CI =& get_instance();
		$upload_path = $CI->config->item('upload_path');

		if (strpos($file, $upload_path) !== 0) {
			throw new \exceptions\ApiException("libraries/ddownload/lighttpd/invalid-file-path", 'Invalid file path');
		}

		header('Content-disposition: inline; filename="'.$filename."\"\n");
		header('Content-Type: '.$type."\n");
		header('X-Sendfile: '.$file."\n");
	}

}
