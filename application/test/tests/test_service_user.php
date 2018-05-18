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

		$ret = \service\user::delete_invitation_key($userid+1, $key);
		$this->t->is(0, $ret, "Should have removed no keys because incorrect user/key");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key after incorrect deletion");

		$ret = \service\user::delete_invitation_key($userid+1, "foobar-");
		$this->t->is(0, $ret, "Should have removed no keys because incorrect user/key");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key after incorrect deletion");

		$ret = \service\user::delete_invitation_key($userid+1, "");
		$this->t->is(0, $ret, "Should have removed no keys because incorrect user/key");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key after incorrect deletion");

		$ret = \service\user::delete_invitation_key($userid, "");
		$this->t->is(0, $ret, "Should have removed no keys because incorrect user/key");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([['user' => "".$userid, 'key' => $key, 'action' => 'invitation']], $result, "database contains new key");

		$ret = \service\user::delete_invitation_key($userid, $key);
		$this->t->is(1, $ret, "One key should be removed");
		$result = $CI->db->select('user, key, action')->from('actions')->get()->result_array();
		$this->t->is_deeply([], $result, "key has been deleted");

	}

}

