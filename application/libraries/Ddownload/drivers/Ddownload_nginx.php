<?php
/*
 * Copyright 2013 Pierre Schmitz <pierre@archlinux.de>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Ddownload_nginx extends Ddownload_Driver {

	public function serveFile($file, $filename, $type)
	{
		$CI =& get_instance();
		$upload_path = $CI->config->item('upload_path');
		$download_location = $CI->config->item('download_nginx_location');

		if (strpos($file, $upload_path) === 0) {
			$file_path = substr($file, strlen($upload_path));
		} else {
			throw new \exceptions\ApiException("libraries/ddownload/nginx/invalid-file-path", 'Invalid file path');
		}

		header('Content-disposition: inline; filename="'.$filename."\"\n");
		header('Content-Type: '.$type."\n");
		header('X-Accel-Redirect: '.$download_location.$file_path."\n");
	}

}
