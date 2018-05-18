<?php
/*
 * Copyright 2018 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace test\tests;

class test_service_user extends \test\Test {

	public function __construct() {
		parent::__construct();
	}

	public function init() {
	}

	public function cleanup() {
	}

	public function test_invitation_key_delete() {
		$CI =& get_instance();

		$userid = 1;

		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([], $result, "database contains no actions");

		$key = \service\user::create_invitation_key($userid);

		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key");

		\service\user::delete_invitation_key($userid+1, $key);
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key after incorrect deletion");

		\service\user::delete_invitation_key($userid+1, "foobar-");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key after incorrect deletion");

		\service\user::delete_invitation_key($userid+1, "");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key after incorrect deletion");

		\service\user::delete_invitation_key($userid, "");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key");

		\service\user::delete_invitation_key($userid, $key);

		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([], $result, "key has been deleted");

	}

}

