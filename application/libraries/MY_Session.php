<?php
/*
 * Copyright 2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class MY_Session extends CI_Session {
	private $memory_only = false;

	public function __construct() {
		$CI =& get_instance();
		$CI->load->helper("filebin");

		/* Clients using API keys do not need a persistent session since API keys
		 * should be sent with each request. This reduces database queries and
		 * prevents us from sending useless cookies.
		 */
		if (!stateful_client()) {
			$this->memory_only = true;
			$CI->config->set_item("sess_use_database", false);
		}

		parent::__construct();
	}

	public function _set_cookie($cookie_data = NULL)
	{
		if ($this->memory_only) {
			return;
		}

		parent::_set_cookie($cookie_data);

	}
}
