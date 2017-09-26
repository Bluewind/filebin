<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace controllers\api;

abstract class api_controller {
	public function __construct() {
		$this->CI =& get_instance();
	}

}

