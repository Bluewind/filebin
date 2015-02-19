<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Mmultipaste extends CI_Model {

	function __construct()
	{
		parent::__construct();
		$this->load->model("muser");
		$this->load->model("mfile");
	}

	/**
	 * Returns an unused ID
	 *
	 * @param min minimal length of the resulting ID
	 * @param max maximum length of the resulting ID
	 */
	public function new_id($min = 3, $max = 6)
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
			$id = "m-".random_alphanum($min, $max);

			// TODO: try to insert the id into file_groups instead of checking with
			// id_exists (prevents race conditio)
			if ($this->id_exists($id) || in_array($id, $id_blacklist)) {
				continue;
			}

			$this->db->insert("multipaste", array(
				"url_id" => $id,
				"user_id" => $this->muser->get_userid(),
				"date" => time(),
			));

			return $id;
		}

		throw new \exceptions\PublicApiException("file/new_id-try-limit", "Failed to find unused ID after $max_tries tries");
	}

	public function id_exists($id)
	{
		if (!$id) {
			return false;
		}

		$sql = '
			SELECT url_id
			FROM `'.$this->db->dbprefix.'multipaste`
			WHERE url_id = ?
			LIMIT 1';
		$query = $this->db->query($sql, array($id));

		if ($query->num_rows() == 1) {
			return true;
		} else {
			return false;
		}
	}

	public function valid_id($id)
	{
		$files = $this->get_files($id);
		foreach ($files as $file) {
			if (!$this->mfile->valid_id($file["id"])) {
				return false;
			}
		}
		return true;
	}

	function adopt($id)
	{
		$userid = $this->muser->get_userid();

		$this->db->set(array('user_id' => $userid ))
			->where('url_id', $id)
			->where('user_id', 0)
			->update('multipaste');
		return $this->db->affected_rows();
	}

	public function get_tarball_path($id)
	{
		return $this->config->item("upload_path")."/special/multipaste-tarballs/".substr(md5($id), 0, 3)."/$id.tar.gz";
	}

	public function delete_id($id)
	{
		$this->db->where('url_id', $id)
			->delete('multipaste');

		$path = $this->get_tarball_path($id);
		$f = new \service\storage($this->get_tarball_path($id));
		$f->unlink();

		if ($this->id_exists($id))  {
			return false;
		}

		return true;
	}

	public function get_owner($id)
	{
		return $this->db->query("
			SELECT user_id
			FROM `".$this->db->dbprefix."multipaste`
			WHERE url_id = ?
			", array($id))->row_array()["user_id"];
	}

	public function get_multipaste($id)
	{
		return $this->db->query("
			SELECT url_id, user_id, date
			FROM `".$this->db->dbprefix."multipaste`
			WHERE url_id = ?
			", array($id))->row_array();
	}

	public function get_files($url_id)
	{
		$ret = array();

		$query = $this->db->query("
			SELECT mfm.file_url_id
			FROM `".$this->db->dbprefix."multipaste_file_map` mfm
			JOIN `".$this->db->dbprefix."multipaste` m ON m.multipaste_id = mfm.multipaste_id
			WHERE m.url_id = ?
			ORDER BY mfm.sort_order
			", array($url_id))->result_array();

		foreach ($query as $row) {
			$filedata = $this->mfile->get_filedata($row["file_url_id"]);
			$ret[] = $filedata;
		}

		return $ret;
	}

	public function get_multipaste_id($url_id)
	{
		$query = $this->db->query("
			SELECT multipaste_id
			FROM `".$this->db->dbprefix."multipaste`
			WHERE url_id = ?
			", array($url_id));

		if ($query->num_rows() > 0) {
			return $query->row_array()["multipaste_id"];
		}

		return false;
	}

}
