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
		// TODO: implement
	}
}
