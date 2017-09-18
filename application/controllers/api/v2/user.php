<?php
/*
 * Copyright 2014-2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace controllers\api\v2;

class user extends \controllers\api\api_controller {
	public function __construct()
	{
		parent::__construct();

		$this->CI->load->model('muser');
	}

	public function apikeys()
	{
		$this->CI->muser->require_access("full");
		return \service\user::apikeys($this->CI->muser->get_userid());
	}

	public function create_apikey()
	{
		$username = $this->CI->input->post("username");
		$password = $this->CI->input->post("password");
		if ($username && $password) {
			if (!$this->CI->muser->login($username, $password)) {
				throw new \exceptions\NotAuthenticatedException("user/login-failed", "Login failed");
			}
		}

		$this->CI->muser->require_access("full");

		$userid = $this->CI->muser->get_userid();
		$comment = $this->CI->input->post("comment");
		$comment = $comment === null ? "" : $comment;
		$access_level = $this->CI->input->post("access_level");

		$key = \service\user::create_apikey($userid, $comment, $access_level);

		return array(
			"new_key" => $key,
		);
	}

	public function delete_apikey()
	{
		$this->CI->muser->require_access("full");

		$userid = $this->CI->muser->get_userid();
		$key = $this->CI->input->post("delete_key");

		$this->CI->db->where('user', $userid)
			->where('key', $key)
			->delete('apikeys');

		$affected = $this->CI->db->affected_rows();

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
