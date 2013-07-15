<?php
/*
 * Copyright 2012-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

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

	function test_login()
	{
		$username = $this->input->post('username');
		$password = $this->input->post('password');

		if ($this->muser->login($username, $password)) {
			$this->output->set_status_header(204);
		} else {
			$this->output->set_status_header(401);
		}
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
		$this->duser->require_implemented("can_register_new_users");
		$this->muser->require_access();

		$userid = $this->muser->get_userid();

		// TODO: count both, invited users and key
		$query = $this->db->query("
			SELECT count(*) as count
			FROM `actions`
			WHERE `user` = ?
			AND `action` = 'invitation'
			", array($userid))->row_array();

		if ($query["count"] + 1 > 3) {
			show_error("You can't create more invitation keys at this time.");
		}

		$key = random_alphanum(12, 16);

		$this->db->query("
			INSERT INTO `actions`
			(`key`, `user`, `date`, `action`)
			VALUES (?, ?, ?, 'invitation')
		", array($key, $userid, time()));

		redirect("user/invite");
	}

	function invite()
	{
		$this->duser->require_implemented("can_register_new_users");
		$this->muser->require_access();

		$userid = $this->muser->get_userid();

		$query = $this->db->query("
			SELECT `key`, `date`
			FROM `actions`
			WHERE `user` = ?
			AND `action` = 'invitation'
			", array($userid))->result_array();

		$this->data["query"] = $query;

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'invite', $this->data);
		$this->load->view('footer', $this->data);
	}

	function register()
	{
		$this->duser->require_implemented("can_register_new_users");
		$key = $this->uri->segment(3);
		$process = $this->input->post("process");
		$values = array(
			"username" => "",
			"email" => ""
		);
		$error = array();

		$query = $this->muser->get_action("invitation", $key);

		$referrer = $query["user"];

		if ($process !== false) {
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
					DELETE FROM actions
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

	// This routes the different steps of a password reset
	function reset_password()
	{
		$this->duser->require_implemented("can_reset_password");
		$key = $this->uri->segment(3);

		if ($_SERVER["REQUEST_METHOD"] == "GET" && $key === false) {
			return $this->_reset_password_username_form();
		}

		if ($key === false) {
			return $this->_reset_password_send_mail();
		}

		if ($key !== false) {
			return $this->_reset_password_form();
		}
	}

	// This simply queries the username
	function _reset_password_username_form()
	{
		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'reset_password_username_form', $this->data);
		$this->load->view('footer', $this->data);
	}

	// This sends a mail to the user containing the reset link
	function _reset_password_send_mail()
	{
		$key = random_alphanum(12, 16);
		$username = $this->input->post("username");

		if (!$this->muser->username_exists($username)) {
			show_error("Invalid username");
		}

		$userinfo = $this->db->query("
			SELECT id, email, username
			FROM users
			WHERE username = ?
			", array($username))->row_array();

		$this->load->library("email");

		$this->db->query("
			INSERT INTO `actions`
			(`key`, `user`, `date`, `action`)
			VALUES (?, ?, ?, 'passwordreset')
			", array($key, $userinfo["id"], time()));

		$admininfo = $this->db->query("
			SELECT email
			FROM users
			WHERE referrer = 0
			ORDER BY id asc
			LIMIT 1
			")->row_array();

		$this->email->from($admininfo["email"]);
		$this->email->to($userinfo["email"]);
		$this->email->subject("FileBin password reset");
		$this->email->message(""
			."Someone requested a password reset for the account '${userinfo["username"]}'\n"
			."from the IP address '${_SERVER["REMOTE_ADDR"]}'.\n"
			."\n"
			."Please follow this link to reset your password:\n"
			.site_url("user/reset_password/$key")
			);
		$this->email->send();

		$this->data["email"] = $userinfo["email"];

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'reset_password_link_sent', $this->data);
		$this->load->view('footer', $this->data);
	}

	// This displays a form and handles the reset if the form has been filled out correctly
	function _reset_password_form()
	{
		$process = $this->input->post("process");
		$key = $this->uri->segment(3);
		$error = array();

		$query = $this->muser->get_action("passwordreset", $key);

		$userid = $query["user"];

		if ($process !== false) {
			$password = $this->input->post("password");
			$password_confirm = $this->input->post("password_confirm");

			if (!$password || $password != $password_confirm) {
				$error[]= "No password or passwords don't match.";
			}

			if (empty($error)) {
				$this->db->query("
					UPDATE users
					SET `password` = ?
					WHERE `id` = ?
					", array($this->muser->hash_password($password), $userid));
				$this->db->query("
					DELETE FROM actions
					WHERE `key` = ?
					", array($key));
				$this->load->view('header', $this->data);
				$this->load->view($this->var->view_dir.'reset_password_success', $this->data);
				$this->load->view('footer', $this->data);
				return;
			}
		}

		$this->data["key"] = $key;
		$this->data["error"] = $error;

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'reset_password_form', $this->data);
		$this->load->view('footer', $this->data);
	}

	function profile()
	{
		$this->muser->require_access();

		if ($this->input->post("process") !== false) {
			$this->_save_profile();
		}

		$this->data["profile_data"] = $this->muser->get_profile_data();

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'profile', $this->data);
		$this->load->view('footer', $this->data);
	}

	private function _save_profile()
	{
		$this->muser->require_access();

		/*
		 * Key = name of the form field
		 * Value = function that sanatizes the value and returns it
		 * TODO: some kind of error handling that doesn't loose correctly filled out fields
		 */
		$value_processor = array();

		$value_processor["upload_id_limits"] = function($value) {
			$values = explode("-", $value);

			if (!is_array($values) || count($values) != 2) {
				show_error("Invalid upload id limit value");
			}

			$lower = intval($values[0]);
			$upper = intval($values[1]);

			if ($lower > $upper) {
				show_error("lower limit > upper limit");
			}

			if ($lower < 3 || $upper > 64) {
				show_error("upper or lower limit out of bounds (3-64)");
			}

			return $lower."-".$upper;
		};

		$data = array();
		foreach (array_keys($value_processor) as $field) {
			$value = $this->input->post($field);

			if ($value !== false) {
				$data[$field] = $value_processor[$field]($value);
			}
		}

		if (!empty($data)) {
			$this->muser->update_profile($data);
		}

		$this->data["alerts"][] = array(
			"type" => "success",
			"message" => "Changes saved",
		);

		return true;
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

		if ($process !== false) {
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

		if ($this->config->item('actions_max_age') == 0) return;

		$oldest_time = (time() - $this->config->item('actions_max_age'));

		$this->db->query("
			DELETE FROM actions
			WHERE date < ?
			", array($oldest_time));
	}
}
