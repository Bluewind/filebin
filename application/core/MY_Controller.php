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
		$csrf_protection = true;

		$this->load->library('migration');
		if ( ! $this->migration->current()) {
			show_error($this->migration->error_string());
		}

		$old_path = getenv("PATH");
		putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

		mb_internal_encoding('UTF-8');
		$this->load->helper(array('form', 'filebin'));

		// TODO: proper accept header handling or is this enough?
		if (isset($_SERVER["HTTP_ACCEPT"])) {
			if ($_SERVER["HTTP_ACCEPT"] == "application/json") {
				static_storage("response_type", "json");
			}
		}

		// Allow for easier testing in browser
		if ($this->input->get("json") !== false) {
			static_storage("response_type", "json");
		}

		if (static_storage("response_type") == "json" && ! in_array($this->uri->rsegment(2), $this->json_enabled_functions)) {
			show_error("Function not JSON enabled");
		}

		if ($this->input->post("apikey") !== false) {
			/* This relies on the authentication code always verifying the supplied
			 * apikey. If the key is not verified/logged in an attacker could simply
			 * add an empty "apikey" field to the CSRF form to circumvent the
			 * protection. If we always log in if a key is supplied we can ensure
			 * that an attacker (and the victim since they get a cookie) can only
			 * access the attacker's account.
			 */
			$csrf_protection = false;
		}

		$uri_start = $this->uri->rsegment(1)."/".$this->uri->rsegment(2);
		$csrf_whitelisted_handlers = array(
			"always" => array(
				/* Whitelist the upload pages because they don't cause harm and a user
				 * might keep the upload page open for more than csrf_expire seconds
				 * and we don't want to annoy them when they upload a big file and the
				 * CSRF check fails.
				 */
				"file/do_upload",
				"file/do_paste",
			),
			"cli_client" => array(
				"file/do_delete",
				"file/delete",
				"file/upload_history",
			),
		);
		if (in_array($uri_start, $csrf_whitelisted_handlers["always"])) {
			$csrf_protection = false;
		}

		// TODO: replace cli client with request_type("plain")?
		if (is_cli_client() && in_array($uri_start, $csrf_whitelisted_handlers["cli_client"])) {
			$csrf_protection = false;
		}

		if ($csrf_protection && !$this->input->is_cli_request()) {
			// 2 functions for accessing config options, really?
			$this->config->set_item('csrf_protection', true);
			config_item("csrf_protection", true);
			$this->security->__construct();
			$this->security->csrf_verify();
		}

		$this->data['title'] = "FileBin";
	}
}
