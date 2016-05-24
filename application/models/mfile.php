<?php
/*
 * Copyright 2009-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Mfile extends CI_Model {

	private $upload_path;

	function __construct()
	{
		parent::__construct();
		$this->load->model("muser");

		$this->upload_path = $this->config->item('upload_path');
		$this->id_validation_config = array(
			"upload_max_age" => $this->config->item("upload_max_age"),
			"small_upload_size" => $this->config->item("small_upload_size"),
			"sess_expiration" => $this->config->item("sess_expiration"),
		);
	}

	// Returns an unused ID
	function new_id($min = 3, $max = 6)
	{
		static $id_blacklist = NULL;

		if ($id_blacklist == NULL) {
			// This prevents people from being unable to access their uploads
			// because of URL rewriting
			$id_blacklist = scandir(FCPATH);
			$id_blacklist[] = "file";
			$id_blacklist[] = "user";
		}

		$max_tries = 100;

		for ($try = 0; $try < $max_tries; $try++) {
			$id = random_alphanum($min, $max);

			if ($this->id_exists($id) || in_array($id, $id_blacklist)) {
				continue;
			}

			return $id;
		}

		throw new \exceptions\PublicApiException("file/new_id-try-limit", "Failed to find unused ID after $max_tries tries");
	}

	function id_exists($id)
	{
		if (!$id) {
			return false;
		}

		$query = $this->db->select('id')
			->from('files')
			->where('id', $id)
			->limit(1)
			->get();

		if ($query->num_rows() == 1) {
			return true;
		} else {
			return false;
		}
	}

	function get_filedata($id)
	{
		return cache_function("filedatav2-$id", 300, function() use ($id) {
			$query = $this->db
				->select('files.id, hash, file_storage.id storage_id, filename, mimetype, files.date, user, filesize')
				->from('files')
				->join('file_storage', 'file_storage.id = files.file_storage_id')
				->where('files.id', $id)
				->limit(1)
				->get();

			if ($query->num_rows() > 0) {
				$data = $query->row_array();
				$data["data_id"] = $data["hash"]."-".$data["storage_id"];
				return $data;
			} else {
				return false;
			}
		});
	}

	// return the folder in which the file with $data_id is stored
	function folder($data_id) {
		return $this->upload_path.'/'.substr($data_id, 0, 3);
	}

	// Returns the full path to the file with $data_id
	function file($data_id) {
		return $this->folder($data_id).'/'.$data_id;
	}

	// Add a file to the DB
	function add_file($id, $filename, $storage_id)
	{
		$userid = $this->muser->get_userid();

		$this->db->insert("files", array(
			"id" => $id,
			"filename" => $filename,
			"date" => time(),
			"user" => $userid,
			"file_storage_id" => $storage_id,
		));
	}

	function adopt($id)
	{
		$userid = $this->muser->get_userid();

		$this->db->set(array('user' => $userid ))
			->where('id', $id)
			->where('user', 0)
			->update('files');
		return $this->db->affected_rows();
	}

	// remove old/invalid/broken IDs
	public function valid_id($id)
	{
		$filedata = $this->get_filedata($id);

		if (!$filedata) {
			return false;
		}

		return $this->valid_filedata($filedata);
	}

	public function valid_filedata($filedata)
	{
		return \service\files::valid_id($filedata, $this->id_validation_config, $this, time());
	}

	public function file_exists($file)
	{
		return file_exists($file);
	}

	public function filemtime($file)
	{
		return filemtime($file);
	}

	public function filesize($file)
	{
		return filesize($file);
	}

	public function get_timeout($id)
	{
		$filedata = $this->get_filedata($id);
		$file = $this->file($filedata["data_id"]);

		if ($this->config->item("upload_max_age") == 0) {
			return -1;
		}

		if (filesize($file) > $this->config->item("small_upload_size")) {
			return $filedata["date"] + $this->config->item("upload_max_age");
		} else {
			return -1;
		}
	}

	public function get_timeout_string($id)
	{
		$timeout = $this->get_timeout($id);

		if ($timeout >= 0) {
			return date("r", $timeout);
		} else {
			return "unknown";
		}
	}

	private function unused_file($data_id)
	{
		list ($hash, $storage_id) = explode("-", $data_id);
		$query = $this->db->select('files.id')
			->from('files')
			->join('file_storage', 'file_storage.id = files.file_storage_id')
			->where('hash', $hash)
			->where('file_storage.id', $storage_id)
			->limit(1)
			->get();

		if ($query->num_rows() == 0) {
			return true;
		} else {
			return false;
		}
	}

	public function delete_id($id)
	{
		$filedata = $this->get_filedata($id);

		// Delete the file and all multipastes using it
		// Note that this does not delete all relations in multipaste_file_map
		// which is actually done by a SQL contraint.
		// TODO: make it work properly without the constraint
		$map = $this->db->select('url_id')
			->distinct()
			->from('multipaste_file_map')
			->join("multipaste", "multipaste.multipaste_id = multipaste_file_map.multipaste_id")
			->where('file_url_id', $id)
			->get()->result_array();

		$this->db->where('id', $id)
			->delete('files');
		delete_cache("filedata-$id");

		foreach ($map as $entry) {
			assert(!empty($entry['url_id']));
			$this->mmultipaste->delete_id($entry["url_id"]);
		}

		if ($this->id_exists($id))  {
			return false;
		}

		if ($filedata !== false) {
			assert(isset($filedata["data_id"]));
			if ($this->unused_file($filedata['data_id'])) {
				$this->delete_data_id($filedata['data_id']);
			}
		}
		return true;
	}

	public function delete_data_id($data_id)
	{
		list ($hash, $storage_id) = explode("-", $data_id);
		
		$this->db->where('id', $storage_id)
			->delete('file_storage');
		if (file_exists($this->file($data_id))) {
			unlink($this->file($data_id));
		}
		$dir = $this->folder($data_id);
		if (file_exists($dir)) {
			if (count(scandir($dir)) == 2) {
				rmdir($dir);
			}
		}
		delete_cache("${data_id}_thumb_150");
	}

	public function get_owner($id)
	{
		return $this->db->select('user')
			->from('files')
			->where('id', $id)
			->get()->row_array()
			['user'];
	}

}

# vim: set noet:
