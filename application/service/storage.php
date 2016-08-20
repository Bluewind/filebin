<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace service;

/**
 * This class allows to change a temporary file and replace the original one atomically
 */
class storage {
	private $path;
	private $tempfile = NULL;

	public function __construct($path)
	{
		assert(!is_dir($path));

		$this->path = $path;
	}

	/**
	 * Create a temp file which can be written to.
	 *
	 * Call commit() once you are done writing.
	 * Call rollback() to remove the file and throw away any data written.
	 *
	 * Calling this multiple times will automatically rollback previous calls.
	 *
	 * @return temp file path
	 */
	public function begin()
	{
		if($this->tempfile !== NULL) {
			$this->rollback();
		}

		$this->tempfile = $this->create_tempfile();

		return $this->tempfile;
	}

	/**
	 * Create a temporary file. You'll need to remove it yourself when no longer needed.
	 *
	 * @return path to the temporary file
	 */
	private function create_tempfile()
	{
		$dir = dirname($this->get_file());
		$prefix = basename($this->get_file());

		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		assert(is_dir($dir));

		return tempnam($dir, $prefix);
	}

	/**
	 * Save the temporary file returned by begin() to the permanent path
	 * (supplied to the constructor) in an atomic fashion.
	 */
	public function commit()
	{
		$ret = rename($this->tempfile, $this->get_file());
		if ($ret) {
			$this->tempfile = NULL;
		}

		return $ret;
	}

	public function exists()
	{
		return file_exists($this->get_file());
	}

	public function get_file()
	{
		return $this->path;
	}

	/**
	 * Throw away any changes made to the temporary file returned by begin()
	 */
	public function rollback()
	{
		if ($this->tempfile !== NULL) {
			unlink($this->tempfile);
			$this->tempfile = NULL;
		}
	}

	public function __destruct()
	{
		$this->rollback();
	}

	/**
	 * GZIPs the temp file
	 *
	 * From http://stackoverflow.com/questions/6073397/how-do-you-create-a-gz-file-using-php
	 * Based on function by Kioob at:
	 * http://www.php.net/manual/en/function.gzwrite.php#34955
	 *
	 * @param integer $level GZIP compression level (default: 6)
	 * @return boolean true if operation succeeds, false on error
	 */
	public function gzip_compress($level = 6){
		if ($this->tempfile === NULL) {
			return;
		}

		$source = $this->tempfile;
		$file = new storage($source);
		$dest = $file->begin();
		$mode = 'wb' . $level;
		$error = false;
		$chunk_size = 1024*512;

		if ($fp_out = gzopen($dest, $mode)) {
			if ($fp_in = fopen($source,'rb')) {
				while (!feof($fp_in)) {
					gzwrite($fp_out, fread($fp_in, $chunk_size));
				}
				fclose($fp_in);
			} else {
				$error = true;
			}
			gzclose($fp_out);
		} else {
			$error = true;
		}

		if ($error) {
			return false;
		} else {
			$file->commit();
			return true;
		}
	}

	/**
	 * Delete the file if it exists.
	 */
	public function unlink()
	{
		if ($this->exists()) {
			unlink($this->get_file());
		}
	}
}

# vim: set noet:
