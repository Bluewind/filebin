<?php
/*
 * Copyright 2014-2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace controllers\api\v2;

class file extends \controllers\api\api_controller {
	public function __construct()
	{
		parent::__construct();

		$this->CI->load->model('mfile');
		$this->CI->load->model('mmultipaste');
	}

	public function upload()
	{
		$this->CI->muser->require_access("basic");

		$files = getNormalizedFILES();

		if (empty($files)) {
			throw new \exceptions\UserInputException("file/no-file", "No file was uploaded or unknown error occurred.");
		}

		\service\files::verify_uploaded_files($files);

		$limits = $this->determine_id_limits();
		$userid = $this->CI->muser->get_userid();
		$urls = array();

		foreach ($files as $file) {
			$id = $this->CI->mfile->new_id($limits[0], $limits[1]);
			\service\files::add_uploaded_file($userid, $id, $file["tmp_name"], $file["name"]);
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
			"upload_max_size" => $this->CI->config->item("upload_max_size"),
			"max_files_per_request" => intval(ini_get("max_file_uploads")),
			"max_input_vars" => intval(ini_get("max_input_vars")),
			"request_max_size" => return_bytes(ini_get("post_max_size")),
		);
	}

	public function history()
	{
		$this->CI->muser->require_access("apikey");
		$history = \service\files::history($this->CI->muser->get_userid());
		foreach ($history['multipaste_items'] as $key => $item) {
			foreach ($item['items'] as $inner_key => $item) {
				unset($history['multipaste_items'][$key]['items'][$inner_key]['sort_order']);
			}
		}

		$history = ensure_json_keys_contain_objects($history, array("items", "multipaste_items"));

		return $history;
	}

	public function delete()
	{
		$this->CI->muser->require_access("apikey");
		$ids = $this->CI->input->post_array("ids");
		$ret = \service\files::delete($ids);

		$ret = ensure_json_keys_contain_objects($ret, array("errors", "deleted"));

		return $ret;
	}

	public function create_multipaste()
	{
		$this->CI->muser->require_access("basic");
		$ids = $this->CI->input->post_array("ids");
		$userid = $this->CI->muser->get_userid();
		$limits = $this->determine_id_limits();

		return \service\files::create_multipaste($ids, $userid, $limits);
	}


	private function determine_id_limits()
	{
		$posted_minlength = $this->CI->input->post('minimum-id-length');
		if (is_null($posted_minlength)) {
			$limits = $this->CI->muser->get_upload_id_limits();
		} else {
			if ((!preg_match("/^\d+$/", $posted_minlength)) || intval($posted_minlength) <= 1 ) {
				throw new \exceptions\UserInputException("file/bad-minimum-id-length", "Passed parameter 'minimum-id-length' is not a valid integer or too small (min value: 2)");
			}

			$limits = [$posted_minlength, null];
		}

		return $limits;
	}
}
# vim: set noet:
