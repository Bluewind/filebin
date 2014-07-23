<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Tools extends MY_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('mfile');
		if (!$this->input->is_cli_request()) {
			show_error("This can only be called via CLI");
		}
	}

	function index()
	{
		echo "php index.php <controller> <function> [arguments]\n";
		echo "\n";
		echo "Functions:\n";
		echo "  file cron               Cronjob\n";
		echo "  file nuke_id <ID>       Nukes all IDs sharing the same hash\n";
		echo "  user cron               Cronjob\n";
		echo "  tools update_database   Update/Initialise the database\n";
		echo "\n";
		echo "Functions that shouldn't have to be run:\n";
		echo "  file clean_stale_files     Remove files without database entries\n";
		echo "  file update_file_metadata  Update filesize and mimetype in database\n";
		exit;
	}

	function update_database()
	{
		$this->load->library('migration');
		if ( ! $this->migration->current()) {
			show_error($this->migration->error_string());
		}
	}
}
