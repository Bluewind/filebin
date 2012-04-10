<?php

class User extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->library('migration');
		if ( ! $this->migration->current()) {
			show_error($this->migration->error_string());
		}

		$this->load->model("muser");
		$this->data["title"] = "FileBin";
		
		$this->load->helper(array('form', 'filebin'));

		$this->var->view_dir = "user/";
		$this->data['username'] = $this->muser->get_username();
	}
	
	function index()
	{
		$this->data["username"] = $this->muser->get_username();

		$this->load->view($this->var->view_dir.'header', $this->data);
		$this->load->view($this->var->view_dir.'index', $this->data);
		$this->load->view($this->var->view_dir.'footer', $this->data);
	}
	
	function login()
	{
		$this->session->keep_flashdata("uri");

		if ($this->input->post('process')) {
			$username = $this->input->post('username');
			$password = $this->input->post('password');

			$result = $this->muser->login($username, $password);

			if ($result !== true) {
				$data['login_error'] = true;
				$this->load->view($this->var->view_dir.'header', $this->data);
				$this->load->view($this->var->view_dir.'login', $this->data);
				$this->load->view($this->var->view_dir.'footer', $this->data);
			} else {
				$uri = $this->session->flashdata("uri");
				if ($uri) {
					redirect($uri);
				} else {
					redirect("/");
				}
			}
		} else {
			$this->load->view($this->var->view_dir.'header', $this->data);
			$this->load->view($this->var->view_dir.'login', $this->data);
			$this->load->view($this->var->view_dir.'footer', $this->data);
		}
	}

	function create_invitation_key()
	{
		$this->muser->require_access();

		$userid = $this->muser->get_userid();

		// TODO: count both, invited users and key
		$query = $this->db->query("
			SELECT count(*) as count
			FROM invitations
			WHERE user = ?
			", array($userid))->row_array();

		if ($query["count"] + 1 > 3) {
			// TODO: better message
			echo "You've reached your invitation limit.";
			return;
		}

		$key = random_alphanum(12, 16);

		$this->db->query("
			INSERT INTO invitations
			(`key`, `user`, `date`)
			VALUES (?, ?, ?)
		", array($key, $userid, time()));

		redirect("user/invite");
	}

	function invite()
	{
		$this->muser->require_access();

		$userid = $this->muser->get_userid();

		$query = $this->db->query("
			SELECT *
			FROM invitations
			WHERE user = ?
			", array($userid))->result_array();

		$this->data["query"] = $query;

		$this->load->view($this->var->view_dir.'header', $this->data);
		$this->load->view($this->var->view_dir.'invite', $this->data);
		$this->load->view($this->var->view_dir.'footer', $this->data);
	}

	function register()
	{
		$key = $this->uri->segment(3);
		$process = $this->input->post("process");
		$values = array(
			"username" => "",
			"email" => ""
		);
		$error = array();

		$query = $this->db->query("
			SELECT `user`, `key`
			FROM invitations
			WHERE `key` = ?
			", array($key))->row_array();

		if (!isset($query["key"]) || $key != $query["key"]) {
			// TODO: better message
			echo "Unknown key.";
			return;
		}

		$referrer = $query["user"];

		if ($process) {
			$username = $this->input->post("username");
			$email = $this->input->post("email");
			$password = $this->input->post("password");
			$password_confirm = $this->input->post("password_confirm");

			if (!$username || strlen($username) > 32 || !preg_match("/^[a-z0-9]+$/", $username)) {
				$error[]= "Invalid username (only a-z0-9 are allowed).";
			}

			$this->load->helper("email");
			if (!valid_email($email)) {
				$error[]= "Invalid email.";
			}

			if (!$password || $password != $password_confirm) {
				$error[]= "No password or passwords don't match.";
			}

			if (empty($error)) {
				$this->db->query("
					INSERT INTO users
					(`username`, `password`, `email`, `referrer`)
					VALUES(?, ?, ?, ?)
					", array(
						$username,
						$this->muser->hash_password($password),
						$email,
						$referrer
					));
				$this->db->query("
					DELETE FROM invitations
					WHERE `key` = ?
					", array($key));
				$this->load->view($this->var->view_dir.'header', $this->data);
				$this->load->view($this->var->view_dir.'registered', $this->data);
				$this->load->view($this->var->view_dir.'footer', $this->data);
				return;
			} else {
				$values["username"] = $username;
				$values["email"] = $email;
			}
		}

		$this->data["key"] = $key;
		$this->data["values"] = $values;
		$this->data["error"] = $error;

		$this->load->view($this->var->view_dir.'header', $this->data);
		$this->load->view($this->var->view_dir.'register', $this->data);
		$this->load->view($this->var->view_dir.'footer', $this->data);
	}
	
	function logout()
	{
		$this->muser->logout();
		redirect('/');
	}
	
	function hash_password()
	{
		$password = $this->input->post("password");
		echo "hashing $password: ";
		echo $this->muser->hash_password($password);
	}
}
