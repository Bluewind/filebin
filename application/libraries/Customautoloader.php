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
		$namespaces = array(
			'Endroid\QrCode' => [
				["path" => APPPATH."/third_party/QrCode/src/"],
			],
			'' => [
				["path" => APPPATH],
				["path" => APPPATH."/third_party/mockery/library/"]
			],
		);

		foreach ($namespaces as $namespace => $search_items) {
			if ($namespace === '' || strpos($className, $namespace) === 0) {
				foreach ($search_items as $search_item) {
					$nameToLoad = str_replace($namespace, '', $className);
					$path = $search_item['path'].str_replace('\\', DIRECTORY_SEPARATOR, $nameToLoad).'.php';
					if (file_exists($path)) {
						require $path;
						return;
					}
				}
			}
		}
	}
}
