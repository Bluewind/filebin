<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Api extends MY_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->load->model('mfile');
		$this->load->model('mmultipaste');
	}

	public function route() {
		$requested_version = $this->uri->segment(2);
		$function = $this->uri->segment(3);
		$major = intval(explode(".", $requested_version)[0]);

		$class = "controllers\\api\\v".$major;

		if (!class_exists($class) || version_compare($class::get_version(), $requested_version, "<")) {
			return send_json_error_reply("Requested API version is not supported");
		}

		if (!preg_match("/^[a-zA-Z-_]+$/", $function)) {
			return send_json_error_reply("Invalid function requested");
		}

		$controller = new $class;
		return $controller->$function();
	}
}
