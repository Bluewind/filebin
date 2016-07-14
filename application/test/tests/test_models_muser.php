<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_models_muser extends \test\Test {

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

	public function test_valid_username()
	{
		$CI =& get_instance();

		$this->t->is($CI->muser->valid_username("thisisbob42"), true, "valid username");
		$this->t->is($CI->muser->valid_username("31337"), true, "valid username");
		$this->t->is($CI->muser->valid_username("thisisjoe"), true, "valid username");
		$this->t->is($CI->muser->valid_username("1234567890123456789012345678901"), true, "31 chars");
		$this->t->is($CI->muser->valid_username("12345678901234567890123456789012"), true, "32 chars");

		$this->t->is($CI->muser->valid_username("Joe"), false, "contains uppercase");
		$this->t->is($CI->muser->valid_username("joe_bob"), false, "contains underscore");
		$this->t->is($CI->muser->valid_username("joe-bob"), false, "contains dash");
		$this->t->is($CI->muser->valid_username("123456789012345678901234567890123"), false, "33 chars");
		$this->t->is($CI->muser->valid_username("1234567890123456789012345678901234"), false, "34 chars");
	}

	public function test_valid_email()
	{
		$CI =& get_instance();

		$this->t->is($CI->muser->valid_email("joe@bob.com"), true, "valid email");
		$this->t->is($CI->muser->valid_email("joe+mailbox@bob.com"), true, "valid email");
		$this->t->is($CI->muser->valid_email("bob@fancyaddress.net"), true, "valid email");

		$this->t->is($CI->muser->valid_email("joebob.com"), false, "missing @");
	}

}

