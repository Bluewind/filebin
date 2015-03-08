<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

// Original source: http://stackoverflow.com/a/9526005/953022
class CustomAutoloader{
	public function __construct()
	{
		spl_autoload_register(array($this, 'loader'));
	}

	public function loader($className)
	{
		$base_paths = array(
			APPPATH,
			APPPATH."/third_party/mockery/library/",
		);

		foreach ($base_paths as $base_path) {
			$path = $base_path.str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php';
			if (file_exists($path)) {
				require $path;
				return;
			}
		}
	}
}
