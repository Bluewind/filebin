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

	function __construct()
	{
		parent::__construct();

		if ($this->has_session()) {
			$this->session->keep_flashdata("uri");
		}

		$this->load->helper("filebin");
		$this->load->driver("duser");
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
			return $this->session->userdata('logged_in') == true;
		}

		return false;
	}

	function login($username, $password)
	{
		$this->require_session();
		return $this->duser->login($username, $password);
	}

	private function login_cli_client()
	{
		$username = $this->input->post("username");
		$password = $this->input->post("password");
		$apikey = $this->input->post("apikey");

		if ($apikey !== false) {
			if ($this->apilogin(trim($apikey))) {
				return true;
			}
			show_error("API key login failed", 401);
		}

		// prefer post parameters if either (username or password) is set
		if ($username === false && $password === false) {
			if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
				$username = $_SERVER['PHP_AUTH_USER'];
				$password = $_SERVER['PHP_AUTH_PW'];
			}
		}

		if ($apikey === false && $username !== false && $password !== false) {
			if ($this->login($username, $password)) {
				return true;
			} else {
				show_error("Login failed", 401);
			}
		}
	}

	function apilogin($apikey)
	{
		$this->require_session();

		// FIXME: get username/id from duser or move them to apikeys table
		// (users is empty when using any other driver than duser_db)
		$query = $this->db->query("
			SELECT a.user userid, u.username
			FROM apikeys a
			JOIN users u on a.user = u.id
			WHERE a.key = ?
			", array($apikey))->row_array();

		if (isset($query["userid"])) {
			$this->session->set_userdata('logged_in', true);
			$this->session->set_userdata('username', $query["username"]);
			$this->session->set_userdata('userid', $query["userid"]);
			$this->session->set_userdata('access_level', 'apikey');
			return true;
		}

		return false;
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

	function get_userid()
	{
		if (!$this->logged_in()) {
			return 0;
		}

		return $this->session->userdata("userid");
	}

	function get_email($userid)
	{
		if (!$this->duser->is_implemented("get_email")) {
			return false;
		}

		return $this->duser->get_email($userid);
	}

	private function check_access_level($wanted_level)
	{
		$session_level = $this->session->userdata("access_level");

		// last level has the most access
		$levels = array("apikey", "full");

		$wanted = array_search($wanted_level, $levels);
		$have = array_search($session_level, $levels);

		if ($wanted === false || $have === false) {
			show_error("Failed to determine access level");
		}

		if ($have >= $wanted) {
			return true;
		}

		show_error("Access denied", 403);
	}

	function require_access($wanted_level = "full")
	{
		if ($this->logged_in()) {
			return $this->check_access_level($wanted_level);
		}

		if (is_cli_client()) {
			if ($this->login_cli_client()) {
				return $this->check_access_level($wanted_level);
			}

			echo "FileBin requires you to have an account, please go to the homepage for more information.\n";
			exit();
		}

		// desktop clients get redirected to the login form
		$this->require_session();
		if (!$this->session->userdata("flash:new:uri")) {
			$this->session->set_flashdata("uri", $this->uri->uri_string());
		}
		redirect('user/login');
		exit();
	}

	function username_exists($username)
	{
		return $this->duser->username_exists($username);
	}

	function get_action($action, $key)
	{
		$query = $this->db->query("
			SELECT *
			FROM actions
			WHERE `key` = ?
			AND `action` = ?
			", array($key, $action))->row_array();

		if (!isset($query["key"]) || $key != $query["key"]) {
			show_error("Invalid action key");
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

		$query = $this->db->query("
			SELECT ".implode(", ", array_keys($fields))."
			FROM `profiles`
			WHERE user = ?
			", array($userid))->row_array();

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

	public function get_upload_id_limits()
	{
		$userid = $this->get_userid();

		$query = $this->db->query("
			SELECT upload_id_limits
			FROM `profiles`
			WHERE user = ?
			", array($userid))->row_array();

		if (empty($query)) {
			return explode("-", $this->default_upload_id_limits);
		}

		return explode("-", $query["upload_id_limits"]);
	}

	function hash_password($password)
	{

		require_once APPPATH."third_party/PasswordHash.php";

		$hasher = new PasswordHash(9, false);
		return $hasher->HashPassword($password);
	}

}

