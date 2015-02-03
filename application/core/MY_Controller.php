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

	function __construct()
	{
		parent::__construct();

		$this->var = new StdClass();
		$csrf_protection = true;

		$this->load->library('customautoloader');

		// check if DB is up to date
		if (!$this->input->is_cli_request()) {
			if (!$this->db->table_exists('migrations')){
				throw new \exceptions\PublicApiException("general/db/not-initialized", "Database not initialized. Can't find migrations table. Please run the migration script. (php index.php tools update_database)");
			} else {
				$this->config->load("migration", true);
				$target_version = $this->config->item("migration_version", "migration");

				// TODO: wait 20 seconds for an update so requests don't get lost for short updates?
				$row = $this->db->get('migrations')->row();

				$current_version = $row ? $row->version : 0;
				if ($current_version != $target_version) {
					throw new \exceptions\PublicApiException("general/db/wrong-version", "Database version is $current_version, we want $target_version. Please run the migration script. (php index.php tools update_database)");
				}
			}
		}

		$old_path = getenv("PATH");
		putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

		mb_internal_encoding('UTF-8');
		$this->load->helper(array('form', 'filebin'));

		if ($this->uri->segment(1) == "api") {
			is_cli_client(true);
		}

		if ($this->input->post("apikey") !== false || is_cli_client()) {
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
				"file/do_multipaste",
				"file/upload_history",
				"user/create_apikey",
				"file/get_max_size",
			),
		);
		if (in_array($uri_start, $csrf_whitelisted_handlers["always"])) {
			$csrf_protection = false;
		}

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

		if ($this->config->item("environment") == "development") {
			$this->output->enable_profiler(true);
		}

		$this->data['title'] = "FileBin";

		$this->load->model("muser");
		$this->data["user_logged_in"] = $this->muser->logged_in();
	}
}
