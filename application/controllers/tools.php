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
			throw new \exceptions\ApiException("api/cli-only", "This can only be called via CLI");
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
			throw new \exceptions\ApiException("tools/update_database/migration-error", $this->migration->error_string());
		}
	}

	function drop_all_tables_using_prefix()
	{
		$tables = $this->db->list_tables();
		$prefix = $this->db->dbprefix;
		$tables_to_drop = array();

		foreach ($tables as $table) {
			if (strpos($table, $prefix) === 0) {
				$tables_to_drop[] = $this->db->protect_identifiers($table);
			}
		}

		if (empty($tables_to_drop)) {
			return;
		}

		$this->db->query('SET FOREIGN_KEY_CHECKS = 0');
		$this->db->query('DROP TABLE '.implode(", ", $tables_to_drop));
		$this->db->query('SET FOREIGN_KEY_CHECKS = 1');
	}

	function test()
	{
		global $argv;
		$url = $argv[3];
		$testcase = $argv[4];

		$testclass = '\tests\\'.$testcase;
		$test = new $testclass();
		$test->setServer($url);

		$exitcode = 0;

		$refl = new ReflectionClass($test);
		foreach ($refl->getMethods() as $method) {
			if (strpos($method->name, "test_") === 0) {
				try {
					$test->setTestNamePrefix($method->name." - ");
					$test->init();
					$test->{$method->name}();
					$test->cleanup();
				} catch (\Exception $e) {
					echo "not ok - uncaught exception in $testcase->$method->name\n";
					_actual_exception_handler($e);
					$exitcode = 255;
				}
			}
		}
		if ($exitcode == 0) {
			$test->done_testing();
		} else {
			exit($exitcode);
		}
	}
}
