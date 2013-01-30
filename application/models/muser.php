<?php
/*
 * Copyright 2012-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class Muser extends CI_Model {
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

	function require_access()
	{
		if ($this->logged_in()) {
			return true;
		} else {
			if (is_cli_client()) {
				echo "FileBin requires you to have an account, please go to the homepage for more information.\n";
				exit();
			} else {
				$this->require_session();
				if (!$this->session->userdata("flash:new:uri")) {
					$this->session->set_flashdata("uri", $this->uri->uri_string());
				}
				redirect('user/login');
			}
		}
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

	function hash_password($password)
	{

		require_once APPPATH."third_party/PasswordHash.php";

		$hasher = new PasswordHash(9, false);
		return $hasher->HashPassword($password);
	}

}

