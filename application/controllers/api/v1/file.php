<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace controllers\api\v1;

class file extends \controllers\api\api_controller {
	public function __construct()
	{
		parent::__construct();

		$this->load->model('mfile');
		$this->load->model('mmultipaste');
	}

	public function upload()
	{
		$this->muser->require_access("basic");

		$files = getNormalizedFILES();

		if (empty($files)) {
			throw new \exceptions\PublicApiException("file/no-file", "No file was uploaded or unknown error occurred.");
		}

		\service\files::verify_uploaded_files($files);

		$limits = $this->muser->get_upload_id_limits();
		$urls = array();

		foreach ($files as $file) {
			$id = $this->mfile->new_id($limits[0], $limits[1]);
			\service\files::add_uploaded_file($id, $file["tmp_name"], $file["name"]);
			$ids[] = $id;
			$urls[] = site_url($id).'/';
		}

		return array(
			"ids" => $ids,
			"urls" => $urls,
		);
	}

	public function get_config()
	{
		return array(
			"upload_max_size" => $this->config->item("upload_max_size"),
			"max_files_per_request" => intval(ini_get("max_file_uploads")),
			"max_input_vars" => intval(ini_get("max_input_vars")),
			"request_max_size" => return_bytes(ini_get("post_max_size")),
		);
	}

	public function history()
	{
		$this->muser->require_access("apikey");
		$history = \service\files::history($this->muser->get_userid());
		foreach ($history['items'] as $key => $item) {
			unset($history['items'][$key]['thumbnail']);
		}
		return $history;
	}

	public function delete()
	{
		$this->muser->require_access("apikey");
		$ids = $this->input->post("ids");
		return \service\files::delete($ids);
	}

	public function create_multipaste()
	{
		$this->muser->require_access("basic");
		$ids = $this->input->post("ids");
		$userid = $this->muser->get_userid();
		$limits = $this->muser->get_upload_id_limits();

		return \service\files::create_multipaste($ids, $userid, $limits);
	}
}
# vim: set noet:
