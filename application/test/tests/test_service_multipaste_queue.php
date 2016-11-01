<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_service_multipaste_queue extends \test\Test {

	public function __construct()
	{
		parent::__construct();
	}

	public function init()
	{
		$this->session = \Mockery::mock("Session");
		$this->session->shouldReceive("userdata")->never()->byDefault();
		$this->session->shouldReceive("set_userdata")->never()->byDefault();

		$this->mfile = \Mockery::mock("Mfile");
		$this->mfile->shouldReceive("valid_id")->never()->byDefault();

		$this->mmultipaste = \Mockery::mock("Mmultipaste");
		$this->mmultipaste->shouldReceive("valid_id")->never()->byDefault();

		$this->m = new \service\multipaste_queue($this->session, $this->mfile, $this->mmultipaste);
	}

	public function cleanup()
	{
		\Mockery::close();
	}

	public function test_get()
	{
		$this->session->shouldReceive('userdata')->with("multipaste_queue")->once()->andReturn(false);
		$this->t->is_deeply($this->m->get(), [], "Fresh queue is empty");
	}

	public function test_set()
	{
		$this->session->shouldReceive('set_userdata')->with("multipaste_queue", ['abc', '123'])->once();

		$this->mfile->shouldReceive('valid_id')->with('abc')->once()->andReturn(true);
		$this->mfile->shouldReceive('valid_id')->with('123')->once()->andReturn(true);

		$this->t->is($this->m->set(['abc', '123']), null, "set() should succeed");
	}

	public function test_append()
	{
		$this->session->shouldReceive('userdata')->with("multipaste_queue")->once()->andReturn(false);
		$this->mfile->shouldReceive('valid_id')->with('abc')->times(2)->andReturn(true);
		$this->session->shouldReceive('set_userdata')->with("multipaste_queue", ['abc'])->once();
		$this->t->is($this->m->append(['abc']), null, "append([abc]) should succeed");

		$this->session->shouldReceive('userdata')->with("multipaste_queue")->once()->andReturn(['abc']);
		$this->mfile->shouldReceive('valid_id')->with('123')->once()->andReturn(true);
		$this->session->shouldReceive('set_userdata')->with("multipaste_queue", ['abc', '123'])->once();
		$this->t->is($this->m->append(['123']), null, "append([123]) should succeed");
	}

	public function test_append_itemAlreadyInQueue()
	{
		$this->session->shouldReceive('userdata')->with("multipaste_queue")->once()->andReturn(['abc', '123']);
		$this->mfile->shouldReceive('valid_id')->with('abc')->once()->andReturn(true);
		$this->mfile->shouldReceive('valid_id')->with('123')->once()->andReturn(true);
		$this->session->shouldReceive('set_userdata')->with("multipaste_queue", ['abc', '123'])->once();
		$this->t->is($this->m->append(['abc']), null, "append([abc]) should succeed");
	}

	public function test_append_multipaste()
	{
		$this->session->shouldReceive('userdata')->with("multipaste_queue")->once()->andReturn([]);
		$this->mmultipaste->shouldReceive('valid_id')->with('m-abc')->once()->andReturn(true);
		$this->mmultipaste->shouldReceive('get_files')->with('m-abc')->once()->andReturn([
			['id' => 'abc'],
			['id' => '123'],
		]);
		$this->mfile->shouldReceive('valid_id')->with('abc')->once()->andReturn(true);
		$this->mfile->shouldReceive('valid_id')->with('123')->once()->andReturn(true);
		$this->session->shouldReceive('set_userdata')->with("multipaste_queue", ['abc', '123'])->once();
		$this->t->is($this->m->append(['m-abc']), null, "append([m-abc]) should succeed");
	}


}

