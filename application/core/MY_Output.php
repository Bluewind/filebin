<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class MY_Output extends CI_Output {
	public function __construct()
	{
		parent::__construct();
		$this->parse_exec_vars = false;
	}
}
