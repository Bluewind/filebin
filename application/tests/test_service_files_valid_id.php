<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace tests;

class test_service_files_valid_id extends Test {
	private $model;
	private $filedata;
	private $config;

	public function __construct()
	{
		parent::__construct();

		$CI =& get_instance();
		$CI->load->model("muser");
		$CI->load->model("mfile");

	}

	public function init()
	{
		$this->model = \Mockery::mock("Mfile");
		$this->model->shouldReceive("delete_id")->never()->byDefault();
		$this->model->shouldReceive("delete_hash")->never()->byDefault();
		$this->model->shouldReceive("file")->with("file-hash-1")->andReturn("/invalid/path/file-1")->byDefault();
		$this->model->shouldReceive("filemtime")->with("/invalid/path/file-1")->andReturn(500)->byDefault();
		$this->model->shouldReceive("filesize")->with("/invalid/path/file-1")->andReturn(50*1024)->byDefault();
		$this->model->shouldReceive("file_exists")->with("/invalid/path/file-1")->andReturn(true)->byDefault();

		$this->filedata = array(
			"hash" => "file-hash-1",
			"id" => "file-id-1",
			"user" => 2,
			"date" => 500,
		);

		$this->config = array(
			"upload_max_age" => 20,
			"sess_expiration" => 10,
			"small_upload_size" => 10*1024,
		);
	}

	public function cleanup()
	{
		\Mockery::close();
	}

	public function test_valid_id_keepNormalUpload()
	{
		$ret = \service\files::valid_id($this->filedata, $this->config, $this->model, 505);
		$this->t->is($ret, true, "normal case should be valid");
	}

	public function test_valid_id_keepSmallUpload()
	{
		$this->model->shouldReceive("filesize")->with("/invalid/path/file-1")->once()->andReturn(50);

		$ret = \service\files::valid_id($this->filedata, $this->config, $this->model, 550);
		$this->t->is($ret, true, "file is old, but small and should be kept");
	}

	public function test_valid_id_removeOldFile()
	{
		$this->model->shouldReceive("delete_hash")->with("file-hash-1")->once();

		$ret = \service\files::valid_id($this->filedata, $this->config, $this->model, 550);
		$this->t->is($ret, false, "file is old and should be removed");
	}

	public function test_valid_id_removeOldUpload()
	{
		$this->model->shouldReceive("delete_id")->with("file-id-1")->once();
		$this->model->shouldReceive("filemtime")->with("/invalid/path/file-1")->once()->andReturn(540);

		$ret = \service\files::valid_id($this->filedata, $this->config, $this->model, 550);
		$this->t->is($ret, false, "upload is old and should be removed");
	}

	public function test_valid_id_keepNormalUnownedFile()
	{
		$this->filedata["user"] = 0;

		$ret = \service\files::valid_id($this->filedata, $this->config, $this->model, 505);
		$this->t->is($ret, true, "upload is unowned and should be kept");
	}

	public function test_valid_id_removeOldUnownedFile()
	{
		$this->model->shouldReceive("delete_id")->with("file-id-1")->once();
		$this->filedata["user"] = 0;

		$ret = \service\files::valid_id($this->filedata, $this->config, $this->model, 515);
		$this->t->is($ret, false, "upload is old, unowned and should be removed");
	}

	public function test_valid_id_removeMissingFile()
	{
		$this->model->shouldReceive("file_exists")->with("/invalid/path/file-1")->once()->andReturn(false);
		$this->model->shouldReceive("delete_hash")->with("file-hash-1")->once();

		$ret = \service\files::valid_id($this->filedata, $this->config, $this->model, 505);
		$this->t->is($ret, false, "missing file should be removed");
	}

}

