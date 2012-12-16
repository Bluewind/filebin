<?php

class User extends CI_Controller {

	public $data = array();
	public $var;

	function __construct()
	{
		parent::__construct();

		$this->var = new StdClass();

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

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'index', $this->data);
		$this->load->view('footer', $this->data);
	}
	
	function login()
	{
		$this->muser->require_session();
		$this->session->keep_flashdata("uri");

		if ($this->input->post('process') !== false) {
			$username = $this->input->post('username');
			$password = $this->input->post('password');

			$result = $this->muser->login($username, $password);

			if ($result !== true) {
				$this->data['login_error'] = true;
				$this->load->view('header', $this->data);
				$this->load->view($this->var->view_dir.'login', $this->data);
				$this->load->view('footer', $this->data);
			} else {
				$uri = $this->session->flashdata("uri");
				if ($uri) {
					redirect($uri);
				} else {
					redirect("/");
				}
			}
		} else {
			$this->load->view('header', $this->data);
			$this->load->view($this->var->view_dir.'login', $this->data);
			$this->load->view('footer', $this->data);
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
			show_error("You can't create more invitation keys at this time.");
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
			SELECT `key`, `date`
			FROM invitations
			WHERE user = ?
			", array($userid))->result_array();

		$this->data["query"] = $query;

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'invite', $this->data);
		$this->load->view('footer', $this->data);
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
			show_error("Invalid invitation key.");
		}

		$referrer = $query["user"];

		if ($process) {
			$username = $this->input->post("username");
			$email = $this->input->post("email");
			$password = $this->input->post("password");
			$password_confirm = $this->input->post("password_confirm");

			if (!$username || strlen($username) > 32 || !preg_match("/^[a-z0-9]+$/", $username)) {
				$error[]= "Invalid username (only up to 32 chars of a-z0-9 are allowed).";
			} else {
				if ($this->muser->username_exists($username)) {
					$error[] = "Username already exists.";
				}
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
				$this->load->view('header', $this->data);
				$this->load->view($this->var->view_dir.'registered', $this->data);
				$this->load->view('footer', $this->data);
				return;
			} else {
				$values["username"] = $username;
				$values["email"] = $email;
			}
		}

		$this->data["key"] = $key;
		$this->data["values"] = $values;
		$this->data["error"] = $error;

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'register', $this->data);
		$this->load->view('footer', $this->data);
	}
	
	function logout()
	{
		$this->muser->logout();
		redirect('/');
	}
	
	function hash_password()
	{
		$process = $this->input->post("process");
		$password = $this->input->post("password");
		$password_confirm = $this->input->post("password_confirm");
		$this->data["hash"] = false;
		$this->data["password"] = $password;

		if ($process) {
			if (!$password || $password != $password_confirm) {
				$error[]= "No password or passwords don't match.";
			} else {
				$this->data["hash"] = $this->muser->hash_password($password);
			}
		}

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'hash_password', $this->data);
		$this->load->view('footer', $this->data);
	}

	function cron()
	{
		if (!$this->input->is_cli_request()) return;

		if ($this->config->item('invitations_max_age') == 0) return;

		$oldest_time = (time() - $this->config->item('invitations_max_age'));

		$this->db->query("
			DELETE FROM invitations
			WHERE date < ?
			", array($oldest_time));
	}
}
