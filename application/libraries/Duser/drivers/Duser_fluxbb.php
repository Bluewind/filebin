<?php
/*
 * Copyright 2013 Pierre Schmitz <pierre@archlinux.de>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Duser_fluxbb extends Duser_Driver {

	private $CI = null;
	private $config = array();

	function __construct()
	{
		$this->CI =& get_instance();
		$this->config = $this->CI->config->item('auth_fluxbb');
	}

	public function login($username, $password)
	{
		$query = $this->CI->db->query('
			SELECT username, id
			FROM '.$this->config['database'].'.users
			WHERE username LIKE ? AND password = ?
			', array($username, sha1($password)))->row_array();

		if (!empty($query)) {
			return array(
				'username' => $query['username'],
				'userid' => $query['id']
			);
		} else {
			return false;
		}
	}

	public function username_exists($username)
	{
		$query = $this->CI->db->query('
			SELECT id
			FROM '.$this->config['database'].'.users
			WHERE username LIKE ?
			', array($username));

		if ($query->num_rows() > 0) {
			return true;
		} else {
			return false;
		}
	}
}
