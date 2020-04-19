<?php
/*
 * Copyright 2012-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Muser extends CI_Model {

	private $default_upload_id_limits = "3-6";

	// last level has the most access
	private $access_levels = array("basic", "apikey", "full");

	private $hashalgo;
	private $hashoptions = array();

	function __construct()
	{
		parent::__construct();

		$this->load->helper("filebin");
		$this->load->driver("duser");
		$this->hashalgo = $this->config->item('auth_db')['hashing_algorithm'];
		$this->hashoptions = $this->config->item('auth_db')['hashing_options'];
	}

	function has_session()
	{
		// checking $this doesn't work
		$CI =& get_instance();
		if (property_exists($CI, "session")) {
			return true;
		}

		// Only load the session class if we already have a cookie that might need to be renewed.
		// Otherwise we just create lots of stale sessions.
		if (isset($_COOKIE[$this->config->item("sess_cookie_name")])) {
			$this->load->library("session");
			return true;
		}

		return false;
	}

	function require_session()
	{
		if (!$this->has_session()) {
			$this->load->library("session");
		}
	}

	function logged_in()
	{
		if ($this->has_session()) {
			return $this->session->userdata('logged_in') === true;
		}

		return false;
	}

	function login($username, $password)
	{
		$this->require_session();
		return $this->duser->login($username, $password);
	}

	private function login_api_client()
	{
		$username = $this->input->post("username");
		$password = $this->input->post("password");

		// TODO keep for now. might be useful if adapted to apikeys instead of passwords
		// prefer post parameters if either (username or password) is set
		//if ($username === false && $password === false) {
			//if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
				//$username = $_SERVER['PHP_AUTH_USER'];
				//$password = $_SERVER['PHP_AUTH_PW'];
			//}
		//}

		if ($username !== false && $password !== false) {
			if ($this->login($username, $password)) {
				return true;
			} else {
				throw new \exceptions\NotAuthenticatedException("user/login-failed", "Login failed");
			}
		}

		return null;
	}

	function apilogin($apikey)
	{
		$this->require_session();

		// get rid of spaces and newlines
		$apikey = trim($apikey);

		$query = $this->db->select('user, access_level')
			->from('apikeys')
			->where('key', $apikey)
			->get()->row_array();

		if (isset($query["user"])) {
			$this->session->set_userdata(array(
				'logged_in' => true,
				'username' => '',
				'userid' => $query["user"],
				'access_level' => $query["access_level"],
			));
			return true;
		}

		throw new \exceptions\NotAuthenticatedException("user/api-login-failed", "API key login failed");
	}

	function logout()
	{
		$this->require_session();
		$this->session->unset_userdata('logged_in');
		$this->session->unset_userdata('username');
		$this->session->unset_userdata('userid');
		$this->session->sess_destroy();
	}

	function get_username()
	{
		if (!$this->logged_in()) {
			return "";
		}

		return $this->session->userdata('username');
	}

	/*
	 * Check if a given username is valid.
	 *
	 * Valid usernames contain only lowercase characters and numbers. They are
	 * also <= 32 characters in length.
	 *
	 * @return boolean
	 */
	public function valid_username($username)
	{
		return strlen($username) <= 32 && preg_match("/^[a-z0-9]+$/", $username);
	}

	/**
	 * Check if a given email is valid. Only perform minimal checking since
	 * verifying emails is very very difficuly.
	 *
	 * @return boolean
	 */
	public function valid_email($email)
	{
		return $email === filter_var($email, FILTER_VALIDATE_EMAIL);
	}

	public function add_user($username, $password, $email, $referrer)
	{
		if (!$this->valid_username($username)) {
			throw new \exceptions\UserInputException("user/invalid-username", "Invalid username (only up to 32 chars of a-z0-9 are allowed)");
		} else {
			if ($this->muser->username_exists($username)) {
				throw new \exceptions\UserInputException("user/username-already-exists", "Username already exists");
			}
		}

		if (!$this->valid_email($email)) {
			throw new \exceptions\UserInputException("user/invalid-email", "Invalid email");
		}

		$this->db->set(array(
			'username' => $username,
			'password' => $this->hash_password($password),
			'email'    => $email,
			'referrer' => $referrer
		))
		->insert('users');
	}

	/**
	 * Delete a user.
	 *
	 * @param username
	 * @param password
	 * @return true on sucess, false otherwise
	 */
	public function delete_user($username, $password)
	{
		$this->duser->require_implemented("can_delete_account");

		if ($this->duser->test_login_credentials($username, $password)) {
			$this->delete_user_real($username);
			return true;
		}

		return false;
	}

	/**
	 * Delete a user
	 *
	 * @param username
	 * @return void
	 */
	public function delete_user_real($username)
	{
		$this->duser->require_implemented("can_delete_account");
		$userid = $this->get_userid_by_name($username);
		if ($userid === null) {
			throw new \exceptions\ApiException("user/delete", "User cannot be found", ["username" => $username]);
		}

		$this->db->delete('profiles', array('user' => $userid));

		$this->load->model("mfile");
		$this->load->model("mmultipaste");
		$this->mfile->delete_by_user($userid);
		$this->mmultipaste->delete_by_user($userid);

		# null out user data to keep referer information traceable
		# If referer information was relinked, one user could create many
		# accounts, delete the account that was used to invite them and
		# then cause trouble so that the account that invited him gets
		# banned because the admin thinks that account invited abusers
		$this->db->set(array(
			'username' => null,
			'password' => null,
			'email'    => null,
		))
		->where(array('username' => $username))
		->update('users');
	}

	function get_userid()
	{
		if (!$this->logged_in()) {
			return 0;
		}

		return $this->session->userdata("userid");
	}

	public function get_userid_by_name($username)
	{
		$query = $this->db->select('id')
			->from('users')
			->where('username', $username)
			->get()->row_array();
		if ($query) {
			return $query['id'];
		}

		return null;
	}

	function get_email($userid)
	{
		return $this->duser->get_email($userid);
	}

	public function get_access_levels()
	{
		return $this->access_levels;
	}

	private function check_access_level($wanted_level)
	{
		$session_level = $this->session->userdata("access_level");

		$wanted = array_search($wanted_level, $this->get_access_levels());
		$have = array_search($session_level, $this->get_access_levels());

		if ($wanted === false || $have === false) {
			throw new \exceptions\PublicApiException("api/invalid-accesslevel", "Failed to determine access level");
		}

		if ($have >= $wanted) {
			return;
		}

		throw new \exceptions\InsufficientPermissionsException("api/insufficient-permissions", "Access denied: Access level too low. Required: $wanted_level; Have: $session_level");
	}

	function require_access($wanted_level = "full")
	{
		if ($this->input->post("apikey") !== null) {
			$this->apilogin($this->input->post("apikey"));
		}

		//if (is_api_client()) {
			//$this->login_api_client();
		//}

		if ($this->logged_in()) {
			return $this->check_access_level($wanted_level);
		}

		throw new \exceptions\NotAuthenticatedException("api/not-authenticated", "Not authenticated. FileBin requires you to have an account, please go to the homepage at ".site_url()." for more information.");
	}

	function username_exists($username)
	{
		return $this->duser->username_exists($username);
	}

	function get_action($action, $key)
	{
		$query = $this->db->from('actions')
			->where('key', $key)
			->where('action', $action)
			->get()->row_array();

		if (!isset($query["key"]) || $key !== $query["key"]) {
			throw new \exceptions\UserInputException("user/get_action/invalid-action", "Invalid action key. Has the key been used already?");
		}

		return $query;
	}

	public function get_profile_data()
	{
		$userid = $this->get_userid();

		$fields = array(
			"user" => $userid,
			"upload_id_limits" => $this->default_upload_id_limits,
		);

		$query = $this->db->select(implode(', ', array_keys($fields)))
			->from('profiles')
			->where('user', $userid)
			->get()->row_array();

		if ($query === null) {
			$query = [];
		}

		$extra_fields = array(
			"username" => $this->get_username(),
			"email" => $this->get_email($userid),
		);

		return array_merge($fields, $query, $extra_fields);
	}

	public function update_profile($data)
	{
		assert(is_array($data));

		$data["user"] = $this->get_userid();

		$exists_in_db = $this->db->get_where("profiles", array("user" => $data["user"]))->num_rows() > 0;

		if ($exists_in_db) {
			$this->db->where("user", $data["user"]);
			$this->db->update("profiles", $data);
		} else {
			$this->db->insert("profiles", $data);
		}
	}

	public function set_password($userid, $password) {
		$this->db->where('id', $userid)
			->update('users', array(
				'password' => $this->hash_password($password)
			));
	}

	public function rehash_password($userid, $password, $hash) {
		if (password_needs_rehash($hash, $this->hashalgo, $this->hashoptions)) {
			$this->set_password($userid, $password);
		}
	}

	public function get_upload_id_limits()
	{
		$userid = $this->get_userid();

		$query = $this->db->select('upload_id_limits')
			->from('profiles')
			->where('user', $userid)
			->get()->row_array();

		if (empty($query)) {
			return explode("-", $this->default_upload_id_limits);
		}

		return explode("-", $query["upload_id_limits"]);
	}

	function hash_password($password)
	{
		$hash = password_hash($password, $this->hashalgo, $this->hashoptions);
		if ($hash === false) {
			throw new \exceptions\ApiException('user/hash_password/failed', "Failed to hash password");
		}
		return $hash;
	}

}

