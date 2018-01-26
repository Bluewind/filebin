<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_filebin_helper extends \test\Test {

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

  public function test_expiration_duration()
  {
    $this->t->is(expiration_duration(60*60*24*2), "2 days", "2 days");
    $this->t->is(expiration_duration(60*60*24), "1 day", "1 day");
    $this->t->is(expiration_duration(60*60*2), "2 hours", "2 hours");
    $this->t->is(expiration_duration(60*60), "1 hour", "1 hour");
    $this->t->is(expiration_duration(60*2), "2 minutes", "2 minutes");
    $this->t->is(expiration_duration(60), "1 minute", "1 minute");
    $this->t->is(expiration_duration(59), "59 seconds", "59 seconds");
    $this->t->is(expiration_duration(1), "1 second", "1 second");

    $this->t->is(expiration_duration(60*60*24 + 60*60 + 60), "1 day, 1 hour, 1 minute", "1 day, 1 hour, 1 minute");
    $this->t->is(expiration_duration(60*60*24 + 60*60 + 120), "1 day, 1 hour, 2 minutes", "1 day, 1 hour, 2 minutes");
    $this->t->is(expiration_duration(60*60*24 + 60*60*2 + 60), "1 day, 2 hours, 1 minute", "1 day, 2 hours, 1 minute");
    $this->t->is(expiration_duration(60*60*24 + 60*60*2 + 120), "1 day, 2 hours, 2 minutes", "1 day, 2 hours, 2 minutes");
    $this->t->is(expiration_duration(60*60*24*2 + 60*60 + 60), "2 days, 1 hour, 1 minute", "2 days, 1 hour, 1 minute");
    $this->t->is(expiration_duration(60*60*24*2 + 60*60 + 120), "2 days, 1 hour, 2 minutes", "2 days, 1 hour, 2 minutes");
    $this->t->is(expiration_duration(60*60*24*2 + 60*60*2 + 60), "2 days, 2 hours, 1 minute", "2 days, 2 hours, 1 minute");
    $this->t->is(expiration_duration(60*60*24*2 + 60*60*2 + 120), "2 days, 2 hours, 2 minutes", "2 days, 2 hours, 2 minutes");

    $this->t->is(expiration_duration(60*60*24 + 60*60), "1 day, 1 hour", "1 day, 1 hour");
    $this->t->is(expiration_duration(60*60*24 + 60*60*2), "1 day, 2 hours", "1 day, 2 hours");
    $this->t->is(expiration_duration(60*60*24*2 + 60*60), "2 days, 1 hour", "2 days, 1 hour");
    $this->t->is(expiration_duration(60*60*24*2 + 60*60*2), "2 days, 2 hours", "2 days, 2 hours");

    $this->t->is(expiration_duration(60*60*24 + 60), "1 day, 1 minute", "1 day, 1 minute");
    $this->t->is(expiration_duration(60*60*24 + 120), "1 day, 2 minutes", "1 day, 2 minutes");
    $this->t->is(expiration_duration(60*60*24*2 + 60), "2 days, 1 minute", "2 days, 1 minute");
    $this->t->is(expiration_duration(60*60*2*24 + 120), "2 days, 2 minutes", "2 days, 2 minutes");

    $this->t->is(expiration_duration(60*60 + 60), "1 hour, 1 minute", "1 hour, 1 minute");
    $this->t->is(expiration_duration(60*60 + 120), "1 hour, 2 minutes", "1 hour, 2 minutes");
    $this->t->is(expiration_duration(60*60*2 + 60), "2 hours, 1 minute", "2 hours, 1 minute");
    $this->t->is(expiration_duration(60*60*2 + 120), "2 hours, 2 minutes", "2 hours, 2 minutes");

    $this->t->is(expiration_duration(61), "1 minute, 1 second", "1 minute, 1 second");
    $this->t->is(expiration_duration(62), "1 minute, 2 seconds", "1 minute, 2 seconds");
    $this->t->is(expiration_duration(121), "2 minutes, 1 second", "2 minutes, 1 second");
    $this->t->is(expiration_duration(122), "2 minutes, 2 seconds", "2 minutes, 2 seconds");

    $this->t->is(expiration_duration(60*60*24 + 60*60*23 + 60*59), "1 day, 23 hours, 59 minutes", "1 day, 23 hours, 59 minutes");
    $this->t->is(expiration_duration(60*60*23 + 60*59), "23 hours, 59 minutes", "23 hours, 59 minutes");
    $this->t->is(expiration_duration(60*60*2 + 60*59), "2 hours, 59 minutes", "2 hours, 59 minutes");
  }

	public function test_format_bytes()
	{
		$this->t->is(format_bytes(500), "500B", "500B");
		$this->t->is(format_bytes(1500), "1500B", "1500B");
		$this->t->is(format_bytes(1500*1024), "1500.00KiB", "1500.00KiB");
		$this->t->is(format_bytes(1500*1024*1024), "1500.00MiB", "1500.00MiB");
		$this->t->is(format_bytes(1500*1024*1024*1024), "1500.00GiB", "1500.00GiB");
		$this->t->is(format_bytes(1500*1024*1024*1024*1024), "1500.00TiB", "1500.00TiB");
		$this->t->is(format_bytes(1500*1024*1024*1024*1024*1024), "1500.00PiB", "1500.00PiB");
	}

	public function test_even_odd()
	{
		$this->t->is(even_odd(true), "odd", "odd after reset");
		$this->t->is(even_odd(), "even", "even");
		$this->t->is(even_odd(), "odd", "odd");
		$this->t->is(even_odd(true), "odd", "odd after reset");
	}

	public function test_mb_str_pad()
	{
		$this->t->is(mb_str_pad('test', 6), 'test  ', 'Simple test with length=6');
		$this->t->is(mb_str_pad('çµ«ö', 6), 'çµ«ö  ', 'UTF8 test with length=6');
	}

	public function test_files_are_equal()
	{
		$a1 = FCPATH.'/data/tests/message1.bin';
		$a2 = FCPATH.'/data/tests/message2.bin';
		$b = FCPATH.'/data/tests/simple.pdf';
		$this->t->is(files_are_equal($a1, $a2), false, "Same hash, but different file");
		$this->t->is(files_are_equal($a1, $b), false, "Different filesize");
		$this->t->is(files_are_equal($a1, $a1), true, "Same file");
		$this->t->is(files_are_equal($a2, $a2), true, "Same file");
	}

	public function test_return_bytes()
	{
		$this->t->is(return_bytes("1k"), 1*1024, "1k");
		$this->t->is(return_bytes("1M"), 1*1024*1024, "1M");
		$this->t->is(return_bytes("1G"), 1*1024*1024*1024, "1G");

		try {
			return_bytes("1P");
		} catch (\exceptions\ApiException $e) {
			$this->t->is($e->get_error_id(), 'filebin-helper/invalid-input-unit', "unhandled text: 1P");
		}

		$this->t->is(return_bytes("106954752"), 106954752, "value without unit is returned as int");
	}
}
