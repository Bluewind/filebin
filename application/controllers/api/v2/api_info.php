<?php
/*
 * Copyright 2014-2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace controllers\api\v2;

class api_info extends \controllers\api\api_controller {
	static public function get_version()
	{
		return "2.0.0";
	}
}
