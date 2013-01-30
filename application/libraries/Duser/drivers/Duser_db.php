<?php
/*
 * Copyright 2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class Duser_db extends Duser_Driver {

	public $optional_functions = array(
		'username_exists',
		'can_reset_password',
		'can_register_new_users'
	);

	public function login($username, $password)
	{
		$CI =& get_instance();

		$query = $CI->db->query('
			SELECT username, id, password
			FROM `users`
			WHERE `username` = ?
			', array($username))->row_array();

		if (!isset($query["username"]) || $query["username"] !== $username) {
			return false;
		}

		if (!isset($query["password"])) {
			return false;
		}

		if (crypt($password, $query["password"]) === $query["password"]) {
			return array(
				"username" => $username,
				"userid" => $query["id"]
			);
		} else {
			return false;
		}
	}

	public function username_exists($username)
	{
		$CI =& get_instance();

		$query = $CI->db->query("
			SELECT id
			FROM users
			WHERE username = ?
			", array($username));

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

}
