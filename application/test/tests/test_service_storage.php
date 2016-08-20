<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_service_storage extends \test\Test {

	private $tempdir;

	public function __construct()
	{
		parent::__construct();
	}

	public function init()
	{
		$this->tempdir = trim((new \libraries\ProcRunner(['mktemp', '-d']))->execSafe()['stdout']);
	}

	public function cleanup()
	{
		rmdir($this->tempdir);
	}

	public function test_normalCase()
	{
		$file = $this->tempdir.'/testfile1';
		$storage = new \service\storage($file);

		$this->t->is($storage->get_file(), $file, "get_file returns correct path");

		$a = $storage->begin();
		file_put_contents($a, "teststring1");
		$this->t->is(file_exists($file), false, "Test file doesn't exist yet");
		$this->t->is($storage->exists(), false, "Test file doesn't exist yet");

		$storage->commit();
		$this->t->is(file_exists($file), true, "Test file has been created");
		$this->t->is(file_get_contents($file), 'teststring1', "Test file has correct content");

		unlink($file);
	}

	public function test_existingFile()
	{
		$file = $this->tempdir.'/testfile-existing-file';
		file_put_contents($file, "teststring-old");

		$storage = new \service\storage($file);

		$a = $storage->begin();
		file_put_contents($a, "teststring-changed");
		$this->t->is(file_exists($file), true, "Test file already exists");
		$this->t->is(file_get_contents($file), 'teststring-old', "Test file has old content");

		$storage->commit();
		$this->t->is(file_exists($file), true, "Test file still exists");
		$this->t->is(file_get_contents($file), 'teststring-changed', "Test file has updated content");

		unlink($file);
	}

	public function test_rollback()
	{
		$file = $this->tempdir.'/testfile-rollback';
		file_put_contents($file, "teststring-old");

		$storage = new \service\storage($file);

		$a = $storage->begin();
		file_put_contents($a, "teststring-changed");
		$this->t->is(file_exists($file), true, "Test file already exists");
		$this->t->is(file_get_contents($file), 'teststring-old', "Test file has old content");

		$storage->rollback();
		$this->t->is(file_exists($file), true, "Test file still exists");
		$this->t->is(file_get_contents($file), 'teststring-old', "Test file still has old content");

		unlink($file);
	}

	public function test_gzip_compress()
	{
		$file = $this->tempdir.'/testfile-gzip';
		file_put_contents($file, "teststring-old");

		$storage = new \service\storage($file);

		$a = $storage->begin();
		$new_string = str_repeat("teststring-changed", 500);
		file_put_contents($a, $new_string);

		$ret = $storage->gzip_compress();
		$this->t->is($ret, true, "Compression succeeded");

		$this->t->is(file_exists($file), true, "Test file still exists");
		$this->t->is(file_get_contents($file), 'teststring-old', "Test file still has old content");

		$storage->commit();

		ob_start();
		readgzfile($file);
		$file_content = ob_get_clean();

		$this->t->is_deeply($new_string, $file_content, "File is compressed and has correct content");

		unlink($file);
	}

	public function test_unlink()
	{
		$file = $this->tempdir.'/testfile-unlink';
		file_put_contents($file, "teststring-old");

		$storage = new \service\storage($file);
		$this->t->is(file_exists($file), true, "Test file exists");
		$storage->unlink();
		$this->t->is(file_exists($file), false, "Test file has been removed");
	}

	public function test_unlink_missingFile()
	{
		$file = $this->tempdir.'/testfile-unlink';

		$storage = new \service\storage($file);
		$this->t->is(file_exists($file), false, "Test file does nto exist");
		$storage->unlink();
		$this->t->is(file_exists($file), false, "Test file still doesn't exist");
	}

	public function test_begin_calledMultipleTimes()
	{
		$file = $this->tempdir.'/testfile-begin-multi';
		file_put_contents($file, "teststring-old");

		$storage = new \service\storage($file);
		$a = $storage->begin();
		file_put_contents($a, "blub");

		$b = $storage->begin();
		file_put_contents($b, "second write");

		$storage->commit();
		$this->t->is(file_get_contents($file), "second write", "File contains second write");

		unlink($file);
	}

	public function test_begin_creationOfDir()
	{
		$dir = $this->tempdir.'/testdir/';
		$file = $dir.'testfile';
		$storage = new \service\storage($file);

		$this->t->is(is_dir($dir), false, "Directory does not exist");

		$a = $storage->begin();

		$this->t->is(is_dir($dir), true, "Directory exists");

		$storage->rollback();
		rmdir($dir);
	}

}

