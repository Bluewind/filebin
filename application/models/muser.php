<?php

class Muser extends CI_Model {
	function __construct()
	{
		parent::__construct();
		$this->load->library("session");
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

		if (crypt($password, $query["password"] == $password)) {
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
	}

	function get_username()
	{
		return $this->session->userdata('username');
	}

	function get_userid()
	{
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
			$this->session->set_flashdata("uri", $this->uri->uri_string());
			redirect('user/login');
		}
	}

	function hash_password($password)
	{
		$salt = random_alphanum(22);
		return crypt($password, "$2a$10$$salt$");
	}

}

