<?php
/*
 * Copyright 2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

abstract class Duser_Driver extends CI_Driver {

	// List of optional functions that are implemented
	//
	// Possible values are:
	//  - can_register_new_users (only supported with the DB driver!)
	//  - can_reset_password (only supported with the DB driver!)
	//  - can_change_email (only supported with the DB driver!)
	public $optional_functions = array();

	/*
	 * The returned array should contain the following keys:
	 *  - username string
	 *  - userid INT > 0
	 *
	 * @param username
	 * @param password
	 * @return mixed array on success, false on failure
	 */
	abstract public function login($username, $password);

	/*
	 * @param username
	 * @return boolean true is username exists, false otherwise
	 */
	public function username_exists($username) {
		return null;
	}

	/*
	 * @param userid
	 * @return string email address of the user
	 */
	public function get_email($userid) {
		return null;
	}
}

class Duser extends CI_Driver_Library {

	protected $_adapter = null;

	protected $valid_drivers = array(
		'duser_db', 'duser_ldap', 'duser_fluxbb'
	);

	function __construct()
	{
		$CI =& get_instance();

		$this->_adapter = $CI->config->item("authentication_driver");
	}

	// require an optional function to be implemented
	public function require_implemented($function) {
		if (!$this->is_implemented($function)) {
			throw new \exceptions\PublicApiException("libraries/duser/optional-function-not-implemented", ""
				."Optional function '".$function."' not implemented in user adapter '".$this->_adapter."'. "
				."Requested functionally unavailable.");
		}
	}

	// check if an optional function is implemented
	public function is_implemented($function) {
		if (in_array($function, $this->{$this->_adapter}->optional_functions)) {
			return true;
		}

		return false;
	}

	public function login($username, $password)
	{
		$login_info = $this->{$this->_adapter}->login($username, $password);
		if ($login_info === false) {
			return false;
		}

		$CI =& get_instance();

		$CI->session->set_userdata(array(
			'logged_in' => true,
			'username' => $login_info["username"],
			'userid' => $login_info["userid"],
			'access_level' => 'full',
		));

		return true;
	}

	public function username_exists($username)
	{
		if ($username === false) {
			return false;
		}

		return $this->{$this->_adapter}->username_exists($username);
	}

	public function get_email($userid)
	{
		return $this->{$this->_adapter}->get_email($userid);
	}
}
