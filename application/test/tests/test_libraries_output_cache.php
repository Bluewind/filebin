<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_libraries_output_cache extends \test\Test {

	public function __construct()
	{
		parent::__construct();
	}

	public function init()
	{
	}

	public function cleanup()
	{
	}

	public function test_add()
	{
		$oc = new \libraries\Output_cache();
		$oc->add("teststring");

		ob_start();
		$oc->render();
		$output = ob_get_clean();

		$this->t->is($output, "teststring", "Simple add renders correctly");
	}

	public function test_add_function()
	{
		$oc = new \libraries\Output_cache();
		$oc->add_function(function() {echo "teststring";});

		ob_start();
		$oc->render();
		$output = ob_get_clean();

		$this->t->is($output, "teststring", "Simple add_function renders correctly");
	}

	public function test_add_merge()
	{

		$oc = new \libraries\Output_cache();
		$oc->add_merge(['items' => ["test1\n"]], 'tests/echo-fragment');
		$oc->add_merge(['items' => ["test2\n"]], 'tests/echo-fragment');

		ob_start();
		$oc->render();
		$output = ob_get_clean();

		$this->t->is($output, "listing 2 items:\ntest1\ntest2\n", "Simple add renders correctly");
	}

	public function test_add_merge_mixedViews()
	{

		$oc = new \libraries\Output_cache();
		$oc->add_merge(['items' => ["test1\n"]], 'tests/echo-fragment');
		$oc->add_merge(['items' => ["test2\n"]], 'tests/echo-fragment');
		$oc->add("blub\n");
		$oc->add_merge(['items' => ["test3\n"]], 'tests/echo-fragment');

		ob_start();
		$oc->render();
		$output = ob_get_clean();

		$this->t->is($output, "listing 2 items:\ntest1\ntest2\nblub\nlisting 1 items:\ntest3\n", "Simple add renders correctly");
	}

}

