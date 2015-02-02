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
		return send_json_reply(\service\user::apikeys($this->muser->get_userid()));
	}

	public function create_apikey()
	{
		$this->muser->require_access("full");
		$userid = $this->muser->get_userid();
		$comment = $this->input->post("comment");
		$comment = $comment === false ? "" : $comment;
		$access_level = $this->input->post("access_level");

		$key = \service\user::create_apikey($userid, $comment, $access_level);

		return send_json_reply(array(
			"new_key" => $key,
		));
	}
}
