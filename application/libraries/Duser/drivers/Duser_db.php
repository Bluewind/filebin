<?php
/*
 * Copyright 2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Duser_db extends Duser_Driver {

	/* FIXME: If you use this driver as a template, remove can_reset_password
	 * and can_register_new_users. These features require the DB driver and
	 * will NOT work with other drivers.
	 */
	public $optional_functions = array(
		'can_reset_password',
		'can_register_new_users',
		'can_change_email',
	);

	public function login($username, $password)
	{
		$CI =& get_instance();

		$query = $CI->db->select('username, id, password')
			->from('users')
			->where('username', $username)
			->get()->row_array();

		if (empty($query)) {
			return false;
		}

		if (password_verify($password, $query['password'])) {
			$CI->muser->rehash_password($query['id'], $password, $query['password']);
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

		$query = $CI->db->select('id')
			->from('users')
			->where('username', $username)
			->get();

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}

	public function get_email($userid)
	{
		$CI =& get_instance();

		$query = $CI->db->select('email')
			->from('users')
			->where('id', $userid)
			->get()->row_array();

		if (empty($query)) {
			throw new \exceptions\ApiException("libraries/duser/db/get_email-failed", "Failed to get email address from db");
		}

		return $query["email"];
	}

}
