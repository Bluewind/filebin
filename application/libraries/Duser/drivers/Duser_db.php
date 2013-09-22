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
	);

	public function login($username, $password)
	{
		$CI =& get_instance();

		$query = $CI->db->query('
			SELECT username, id, password
			FROM `users`
			WHERE `username` = ?
			', array($username))->row_array();

		if (empty($query)) {
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

	public function get_email($userid)
	{
		$CI =& get_instance();

		$query = $CI->db->query("
			SELECT email
			FROM users
			WHERE id = ?
			", array($userid))->row_array();

		if (empty($query)) {
			show_error("Failed to get email address from db");
		}

		return $query["email"];
	}

}
