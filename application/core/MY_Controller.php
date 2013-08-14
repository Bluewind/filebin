<?php
/*
 * Copyright 2009-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class MY_Controller extends CI_Controller {
	public $data = array();
	public $var;

	protected $json_enabled_functions = array(
	);

	function __construct()
	{
		parent::__construct();

		$this->var = new StdClass();

		$this->load->library('migration');
		if ( ! $this->migration->current()) {
			show_error($this->migration->error_string());
		}

		$old_path = getenv("PATH");
		putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

		mb_internal_encoding('UTF-8');
		$this->load->helper(array('form', 'filebin'));

		if (isset($_SERVER["HTTP_ACCEPT"])) {
			if ($_SERVER["HTTP_ACCEPT"] == "application/json") {
				request_type("json");
			}
		}

		if (request_type() == "json" && ! in_array($this->uri->rsegment(2), $this->json_enabled_functions)) {
			show_error("Function not JSON enabled");
		}

		$this->data['title'] = "FileBin";
	}
}
