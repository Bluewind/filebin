<?php

class Muser extends CI_Model {
	function __construct()
	{
		parent::__construct();
		$this->load->library("session");
		$this->load->helper("filebin");
		$this->session->keep_flashdata("uri");
	}

	function logged_in()
	{
		return $this->session->userdata('logged_in') == true;
	}

	function login($username, $password) 
	{
		$query = $this->db->query('
			SELECT *
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
			$this->session->set_userdata('logged_in', true);
			$this->session->set_userdata('username', $username);
			return true;
		} else {
			return false;
		}
	}

	function logout()
	{
		$this->session->unset_userdata('logged_in');
		$this->session->unset_userdata('username');
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

		$query = $this->db->query("
			SELECT id
			FROM users
			WHERE username = ?
			", array($this->get_username()))->row_array();
		return $query["id"];
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
				if (!$this->session->userdata("flash:new:uri")) {
					$this->session->set_flashdata("uri", $this->uri->uri_string());
				}
				redirect('user/login');
			}
		}
		exit();
	}

	function hash_password($password)
	{

		require_once APPPATH."third_party/PasswordHash.php";

		$hasher = new PasswordHash(9, false);
		return $hasher->HashPassword($password);
	}

}

