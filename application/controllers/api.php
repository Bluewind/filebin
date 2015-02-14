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
		try {
			$requested_version = $this->uri->segment(2);
			$controller = $this->uri->segment(3);
			$function = $this->uri->segment(4);

			if (!preg_match("/^v([0-9]+)(.[0-9]+){0,2}$/", $requested_version)) {
				throw new \exceptions\PublicApiException("api/invalid-version", "Invalid API version requested");
			}

			$requested_version = substr($requested_version, 1);

			$major = intval(explode(".", $requested_version)[0]);

			if (!preg_match("/^[a-zA-Z-_]+$/", $controller)) {
				throw new \exceptions\PublicApiException("api/invalid-endpoint", "Invalid endpoint requested");
			}

			if (!preg_match("/^[a-zA-Z-_]+$/", $function)) {
				throw new \exceptions\PublicApiException("api/invalid-endpoint", "Invalid endpoint requested");
			}

			$namespace = "controllers\\api\\v".$major;
			$class = $namespace."\\".$controller;
			$class_info = $namespace."\\api_info";

			if (!class_exists($class_info) || version_compare($class_info::get_version(), $requested_version, "<")) {
				throw new \exceptions\PublicApiException("api/version-not-supported", "Requested API version is not supported");
			}

			if (!class_exists($class)) {
				throw new \exceptions\PublicApiException("api/unknown-endpoint", "Unknown endpoint requested");
			}

			$c= new $class;
			if (!method_exists($c, $function)) {
				throw new \exceptions\PublicApiException("api/unknown-endpoint", "Unknown endpoint requested");
			}
			return send_json_reply($c->$function());
		} catch (\exceptions\PublicApiException $e) {
			return send_json_error_reply($e->get_error_id(), $e->getMessage(), $e->get_data());
		} catch (\Exception $e) {
			_log_exception($e);
			return send_json_error_reply("internal-error", "An unhandled internal server error occured");
		}
	}
}
