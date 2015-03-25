<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace controllers\api\v1;

class user extends \controllers\api\api_controller {
	public function __construct()
	{
		parent::__construct();

		$this->load->model('muser');
	}

	public function apikeys()
	{
		$this->muser->require_access("full");
		return \service\user::apikeys($this->muser->get_userid());
	}

	public function create_apikey()
	{
		$this->muser->require_access("full");
		$userid = $this->muser->get_userid();
		$comment = $this->input->post("comment");
		$comment = $comment === false ? "" : $comment;
		$access_level = $this->input->post("access_level");

		$key = \service\user::create_apikey($userid, $comment, $access_level);

		return array(
			"new_key" => $key,
		);
	}

	public function delete_apikey()
	{
		$this->muser->require_access("full");

		$userid = $this->muser->get_userid();
		$key = $this->input->post("delete_key");

		$this->db->where('user', $userid)
			->where('key', $key)
			->delete('apikeys');

		$affected = $this->db->affected_rows();

		assert($affected >= 0 && $affected <= 1);
		if ($affected == 1) {
			return array(
				"deleted_keys" => array(
					$key => array (
						"key" => $key,
					),
				),
			);
		} else {
			throw new \exceptions\PublicApiException('user/delete_apikey/failed', 'Apikey deletion failed. Possibly wrong owner.');
		}
	}
}
