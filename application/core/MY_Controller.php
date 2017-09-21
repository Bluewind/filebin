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

		$this->load->library('customautoloader');

		// check if DB is up to date
		if (!(is_cli() && $this->uri->segment(1) === "tools")) {
			$this->_ensure_database_schema_up_to_date();
		}

		$old_path = getenv("PATH");
		putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

		mb_internal_encoding('UTF-8');
		$this->load->helper(array('form', 'filebin'));

		if ($this->uri->segment(1) == "api") {
			is_api_client(true);
		}

		if ($this->_check_csrf_protection_required()) {
			$this->_setup_csrf_protection();
		}

		if ($this->config->item("environment") == "development") {
			$this->output->enable_profiler(true);
		}

		$this->data['title'] = "FileBin";

		$this->load->model("muser");
		$this->data["user_logged_in"] = $this->muser->logged_in();
		$this->data['redirect_uri'] = $this->uri->uri_string();
		if ($this->muser->has_session()) {
			$this->data['show_multipaste_queue'] = !empty((new \service\multipaste_queue)->get());
		}
	}

	protected function _require_cli_request()
	{
		if (!is_cli()) {
			throw new \exceptions\PublicApiException("api/cli-only", "This function can only be accessed via the CLI interface");
		}
	}

	private function _ensure_database_schema_up_to_date()
	{
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

	private function _check_csrf_protection_required()
	{
		if ($this->input->post("apikey") !== null || is_api_client()) {
			/* This relies on the authentication code always verifying the supplied
			 * apikey. If the key is not verified/logged in an attacker could simply
			 * add an empty "apikey" field to the CSRF form to circumvent the
			 * protection. If we always log in if a key is supplied we can ensure
			 * that an attacker (and the victim since they get a cookie) can only
			 * access the attacker's account.
			 */
			// TODO: perform the apikey login here to make sure this works as expected?
			return false;
		}

		$uri_start = $this->uri->rsegment(1)."/".$this->uri->rsegment(2);
		$csrf_whitelisted_handlers = array(
			"always" => array(
				/* Whitelist the upload pages because they don't cause harm and a user
				 * might keep the upload page open for more than csrf_expire seconds
				 * and we don't want to annoy them when they upload a big file and the
				 * CSRF check fails.
				 */
				"file/do_websubmit",
			),
		);
		if (in_array($uri_start, $csrf_whitelisted_handlers["always"])) {
			return false;
		}

		if (is_cli()) {
			return false;
		}

		return true;
	}

	private function _setup_csrf_protection()
	{
		// 2 functions for accessing config options, really?
		$this->config->set_item('csrf_protection', true);
		config_item("csrf_protection", true);
		$this->security->__construct();
		$this->security->csrf_verify();
	}
}
