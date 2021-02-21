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
		$this->_require_cli_request();
	}

	function index()
	{
		output_cli_usage();
		exit;
	}

	function update_database()
	{
		$this->load->library('migration');
		$upgraded = $this->migration->current();
		if ( ! $upgraded) {
			throw new \exceptions\ApiException("tools/update_database/migration-error", $this->migration->error_string());
		}

		if ($upgraded === true) {
			echo "Already at latest database version. No upgrade performed\n";
			return;
		}

		echo "Database upgraded sucessfully to version: $upgraded\n";
	}

	function drop_all_tables()
	{
		$tables = $this->db->list_tables();
		$prefix = $this->db->dbprefix;
		$tables_to_drop = array();

		foreach ($tables as $table) {
			if ($prefix === "" || strpos($table, $prefix) === 0) {
				$tables_to_drop[] = $this->db->protect_identifiers($table);
			}
		}

		if (empty($tables_to_drop)) {
			return;
		}


		if ($this->db->dbdriver !== 'postgre') {
			$this->db->query('SET FOREIGN_KEY_CHECKS = 0');
		}
		$this->db->query('DROP TABLE '.implode(", ", $tables_to_drop));
		if ($this->db->dbdriver !== 'postgre') {
			$this->db->query('SET FOREIGN_KEY_CHECKS = 1');
		}
	}

	function test()
	{
		global $argv;
		$testcase = $argv[3];

		$testcase = str_replace("application/", "", $testcase);
		$testcase = str_replace("/", "\\", $testcase);
		$testcase = str_replace(".php", "", $testcase);

		$test = new $testcase();

		$exitcode = 0;

		$refl = new ReflectionClass($test);
		foreach ($refl->getMethods() as $method) {
			if (strpos($method->name, "test_") === 0) {
				try {
					$test->setTestNamePrefix($method->name." - ");
					$test->init();
					$test->setTestID("{$testcase}->{$method->name}");
					$test->{$method->name}();
					$test->cleanup();
				} catch (\Exception $e) {
					echo "not ok - uncaught exception in {$testcase}->{$method->name}\n";
					\libraries\ExceptionHandler::exception_handler($e);
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

	function generate_coverage_report()
	{
		include APPPATH."../vendor/autoload.php";
		$filter = new \SebastianBergmann\CodeCoverage\Filter;
		$coverage = new \SebastianBergmann\CodeCoverage\CodeCoverage(
			(new \SebastianBergmann\CodeCoverage\Driver\Selector)->forLineCoverage($filter),
			$filter);
		foreach (glob(FCPATH."/test-coverage-data/*") as $file) {
			$coverage->merge(unserialize(file_get_contents($file)));
		}

		$writer = new \SebastianBergmann\CodeCoverage\Report\Clover();
		$writer->process($coverage, 'code-coverage-report.xml');
		$writer = new \SebastianBergmann\CodeCoverage\Report\Html\Facade();
		$writer->process($coverage, 'code-coverage-report');
		print "Report saved to ./code-coverage-report/index.html\n";
	}
}
