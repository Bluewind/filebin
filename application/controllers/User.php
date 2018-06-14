<?php
/*
 * Copyright 2012-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class User extends MY_Controller {

	function __construct()
	{
		parent::__construct();
	}

	function index()
	{
		if (is_cli()) {
			$this->load->library("../controllers/tools");
			return $this->tools->index();
		}

		$this->data["username"] = $this->muser->get_username();

		$this->load->view('header', $this->data);
		$this->load->view('user/index', $this->data);
		$this->load->view('footer', $this->data);
	}

	function test_login()
	{
		$username = $this->input->post('username');
		$password = $this->input->post('password');

		if ($this->muser->login($username, $password)) {
			$this->output->set_status_header(204);
		} else {
			$this->output->set_status_header(403);
		}
	}

	function login()
	{
		$redirect_uri = $this->input->get("redirect_uri");
		$this->muser->require_session();

		if (!preg_match('/^[0-9a-zA-Z\/_-]*$/', $redirect_uri)) {
			$redirect_uri = '/';
		}

		if ($this->muser->logged_in()) {
			redirect($redirect_uri);
		}

		$this->data['redirect_uri'] = $redirect_uri;

		if ($this->input->post('process') !== null) {
			$username = $this->input->post('username');
			$password = $this->input->post('password');

			$result = $this->muser->login($username, $password);

			if ($result !== true) {
				$this->data['login_error'] = true;
				$this->load->view('header', $this->data);
				$this->load->view('user/login', $this->data);
				$this->load->view('footer', $this->data);
			} else {
				redirect($redirect_uri);
			}
		} else {
			$this->load->view('header', $this->data);
			$this->load->view('user/login', $this->data);
			$this->load->view('footer', $this->data);
		}
	}

	function create_apikey()
	{
		$this->muser->require_access();

		$userid = $this->muser->get_userid();
		$comment = $this->input->post("comment");
		$comment = $comment === null ? "" : $comment;
		$access_level = $this->input->post("access_level");

		if ($access_level === null) {
			$access_level = "apikey";
		}

		$key = \service\user::create_apikey($userid, $comment, $access_level);

		redirect("user/apikeys");
	}

	function delete_apikey()
	{
		$this->muser->require_access();

		$userid = $this->muser->get_userid();
		$key = $this->input->post("key");

		$this->db->where('user', $userid)
			->where('key', $key)
			->delete('apikeys');

		redirect("user/apikeys");
	}

	function apikeys()
	{
		$this->muser->require_access();

		$userid = $this->muser->get_userid();
		$apikeys = \service\user::apikeys($userid);
		$this->data["query"] = $apikeys["apikeys"];

		$this->load->view('header', $this->data);
		$this->load->view('user/apikeys', $this->data);
		$this->load->view('footer', $this->data);
	}

	function create_invitation_key()
	{
		$this->duser->require_implemented("can_register_new_users");
		$this->muser->require_access();

		$userid = $this->muser->get_userid();

		\service\user::create_invitation_key($userid);

		redirect("user/invite");
	}

	function delete_invitation_key()
	{
		$this->duser->require_implemented("can_register_new_users");
		$this->muser->require_access();

		$userid = $this->muser->get_userid();
		$key = $this->input->post("key");

		\service\user::delete_invitation_key($userid, $key);
		redirect("user/invite");
	}

	function invite()
	{
		$this->duser->require_implemented("can_register_new_users");
		$this->muser->require_access();

		$userid = $this->muser->get_userid();

		$query = $this->db->select('key, date')
			->from('actions')
			->where('user', $userid)
			->where('action', 'invitation')
			->get()->result_array();

		$this->data["query"] = $query;

		$this->load->view('header', $this->data);
		$this->load->view('user/invite', $this->data);
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

		$this->data['redirect_uri'] = "/";

		if ($process !== null) {
			$username = $this->input->post("username");
			$email = $this->input->post("email");
			$password = $this->input->post("password");
			$password_confirm = $this->input->post("password_confirm");

			if (!$this->muser->valid_username($username)) {
				$error[]= "Invalid username (only up to 32 chars of a-z0-9 are allowed).";
			} else {
				if ($this->muser->username_exists($username)) {
					$error[] = "Username already exists.";
				}
			}

			if (!$this->muser->valid_email($email)) {
				$error[]= "Invalid email.";
			}

			if (!$password || $password !== $password_confirm) {
				$error[]= "No password or passwords don't match.";
			}

			if (empty($error)) {
				$this->muser->add_user($username, $password, $email, $referrer);

				$this->db->where('key', $key)
					->delete('actions');

				$this->load->view('header', $this->data);
				$this->load->view('user/registered', $this->data);
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
		$this->load->view('user/register', $this->data);
		$this->load->view('footer', $this->data);
	}

	public function delete_account()
	{
		$this->muser->require_access();
		$this->duser->require_implemented("can_delete_account");

		if ($_SERVER["REQUEST_METHOD"] == "GET") {
			return $this->_delete_account_form();
		} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
			return $this->_delete_account_process();
		}
	}

	public function _delete_account_form()
	{
		$this->data['username'] = $this->muser->get_username();

		$this->load->view('header', $this->data);
		$this->load->view('user/delete_account_form', $this->data);
		$this->load->view('footer', $this->data);
	}

	public function _delete_account_process()
	{
		$username = $this->muser->get_username();
		$password = $this->input->post("password");

		$useremail = $this->muser->get_email($this->muser->get_userid());

		if ($this->muser->delete_user($username, $password)) {
			$this->muser->logout();

			$this->load->library("email");
			$this->email->from($this->config->item("email_from"));
			$this->email->to($useremail);
			$this->email->subject("FileBin account deleted");
			$this->email->message(""
				."Your FileBin account '${username}' at ".site_url()."\n"
				."has been permemently deleted.\n"
				."\n"
				."The request has been sent from the IP address '${_SERVER["REMOTE_ADDR"]}'\n"
				."and was confirmed with your password.\n"
				."\n"
				."Thank you for using FileBin!\n"
				);
			$this->email->send();
			unset($this->data['username']);
			unset($this->data['user_logged_in']);

			$this->load->view('header', $this->data);
			$this->load->view('user/delete_account_success', $this->data);
			$this->load->view('footer', $this->data);
			return;
		} else {
			$this->data['alerts'][] = array(
				"type" => "danger",
				"message" => "Your password was incorrect",
			);
			return $this->_delete_account_form();
		}
	}

	// This routes the different steps of a password reset
	function reset_password()
	{
		$this->duser->require_implemented("can_reset_password");
		$key = $this->uri->segment(3);

		if ($_SERVER["REQUEST_METHOD"] == "GET" && $key === null) {
			return $this->_reset_password_username_form();
		}

		if ($key === null) {
			return $this->_reset_password_send_mail();
		}

		if ($key !== null) {
			return $this->_reset_password_form();
		}
	}

	// This simply queries the username
	function _reset_password_username_form()
	{
		$this->data['username'] = $this->muser->get_username();

		$this->load->view('header', $this->data);
		$this->load->view('user/reset_password_username_form', $this->data);
		$this->load->view('footer', $this->data);
	}

	// This sends a mail to the user containing the reset link
	function _reset_password_send_mail()
	{
		$key = random_alphanum(12, 16);
		$username = $this->input->post("username");

		if (!$this->muser->username_exists($username)) {
			throw new \exceptions\PublicApiException("user/reset_password/invalid-username", "Invalid username");
		}

		$userinfo = $this->db->select('id, email, username')
			->from('users')
			->where('username', $username)
			->get()->row_array();

		$this->load->library("email");

		$this->db->set(array(
				'key'    => $key,
				'user'   => $userinfo['id'],
				'date'   => time(),
				'action' => 'passwordreset'
			))
			->insert('actions');

		$this->email->from($this->config->item("email_from"));
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

		// don't disclose full email addresses
		$this->data["email_domain"] = substr($userinfo["email"], strpos($userinfo["email"], "@") + 1);

		$this->load->view('header', $this->data);
		$this->load->view('user/reset_password_link_sent', $this->data);
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

		if ($process !== null) {
			$password = $this->input->post("password");
			$password_confirm = $this->input->post("password_confirm");

			if (!$password || $password !== $password_confirm) {
				$error[]= "No password or passwords don't match.";
			}

			if (empty($error)) {
				$this->muser->set_password($userid, $password);

				$this->db->where('key', $key)
					->delete('actions');

				$this->load->view('header', $this->data);
				$this->load->view('user/reset_password_success', $this->data);
				$this->load->view('footer', $this->data);
				return;
			}
		}

		$this->data["key"] = $key;
		$this->data["error"] = $error;

		$this->load->view('header', $this->data);
		$this->load->view('user/reset_password_form', $this->data);
		$this->load->view('footer', $this->data);
	}

	public function change_email()
	{
		$this->duser->require_implemented("can_change_email");
		$key = $this->uri->segment(3);
		$action = $this->uri->segment(4);

		$alerts = array();

		$query = $this->muser->get_action("change_email", $key);

		$userid = $query["user"];
		$data = json_decode($query['data'], true);

		switch ($action) {
		case 'confirm':
			$this->db->where('id', $userid)
				->update('users', array(
					"email" => $data['new_email'],
				));
			$alerts[] = array(
				"type" => "success",
				"message" => "Your email address has been updated",
			);
			break;
		case 'reject':
			$this->db->where('id', $userid)
				->update('users', array(
					"email" => $data['old_email'],
				));
			foreach ($data['keys'] as $k) {
				$this->db->where('key', $k)
					->delete('actions');
			}
			$alerts[] = array(
				"type" => "success",
				"message" => "Your email change request has been canceled and/or your old email address has been restored",
			);
			break;
		default:
			assert(false);
			break;
		}

		$this->data["alerts"] = $alerts;

		return $this->profile();
	}

	function profile()
	{
		$this->muser->require_access();

		if ($this->input->post("process") !== null) {
			$this->_save_profile();
		}

		$this->data["profile_data"] = $this->muser->get_profile_data();

		$this->load->view('header', $this->data);
		$this->load->view('user/profile', $this->data);
		$this->load->view('footer', $this->data);
	}

	private function _save_profile()
	{
		$this->muser->require_access();

		$old = $this->muser->get_profile_data();

		/*
		 * Key = name of the form field
		 * Value = function that sanatizes the value and returns it
		 * TODO: some kind of error handling that doesn't loose correctly filled out fields
		 */
		$value_processor = array();
		$alerts = array();

		$value_processor["upload_id_limits"] = function($value) {
			$values = explode("-", $value);

			if (!is_array($values) || count($values) != 2) {
				throw new \exceptions\PublicApiException("user/profile/invalid-upload-id-limit", "Invalid upload id limit value");
			}

			$lower = intval($values[0]);
			$upper = intval($values[1]);

			if ($lower > $upper) {
				throw new \exceptions\PublicApiException("user/profile/lower-bigger-than-upper", "lower limit > upper limit");
			}

			if ($lower < 3 || $upper > 64) {
				throw new \exceptions\PublicApiException("user/profile/limit-out-of-bounds", "upper or lower limit out of bounds (3-64)");
			}

			return $lower."-".$upper;
		};

		$value_processor["email"] = function($value) use ($old, &$alerts) {
			if (!$this->duser->is_implemented("can_change_email")) {
				return null;
			}

			if ($value === $old["email"]) {
				return null;
			}

			if (!$this->muser->valid_email($value)) {
				throw new \exceptions\PublicApiException("user/profile/invalid-email", "Invalid email");
			}

			$this->load->library("email");
			$keys = array(
				"old" => random_alphanum(12,16),
				"new" => random_alphanum(12,16),
			);
			$emails = array(
				array(
					"key" => $keys['old'],
					"email" => $old['email'],
					"user" => $this->muser->get_userid(),
				),
				array(
					"key" => $keys['new'],
					"email" => $value,
					"user" => $this->muser->get_userid(),
				),
			);

			foreach ($emails as $email) {
				$key = $email['key'];

				$this->db->set(array(
						'key'    => $key,
						'user'   => $this->muser->get_userid(),
						'date'   => time(),
						'action' => 'change_email',
						'data'   => json_encode(array(
							'old_email' => $old['email'],
							'new_email' => $value,
							'keys' => $keys,
						)),
					))
					->insert('actions');

				$this->email->from($this->config->item("email_from"));
				$this->email->to($email['email']);
				$this->email->subject("FileBin email change confirmation");
				$this->email->message(""
					."A request has been sent to change the email address of account '${old["username"]}'\n"
					."from ".$old['email']." to $value.\n"
					."\n"
					."Please follow this link to CONFIRM the change:\n"
					.site_url("user/change_email/$key/confirm")."\n\n"
					."Please follow this link to REJECT the change:\n"
					.site_url("user/change_email/$key/reject")."\n\n"
					);
				$this->email->send();
				$this->email->clear();
			}

			$alerts[] = array(
				"type" => "info",
				"message" => "Reset and confirmation emails have been sent to your new and old address. Until your new address is confirmed the old one will be displayed and used.",
			);

			return null;
		};


		$data = array();
		foreach (array_keys($value_processor) as $field) {
			$value = $this->input->post($field);

			if ($value !== null) {
				$new_value = $value_processor[$field]($value);
				if ($new_value !== null) {
					$data[$field] = $new_value;
				}
			}
		}

		if (!empty($data)) {
			$this->muser->update_profile($data);
		}

		$alerts[] = array(
			"type" => "success",
			"message" => "Changes saved",
		);
		$this->data["alerts"] = $alerts;

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

		if ($process !== null) {
			if (!$password || $password !== $password_confirm) {
				$error[]= "No password or passwords don't match.";
			} else {
				$this->data["hash"] = $this->muser->hash_password($password);
			}
		}

		$this->load->view('header', $this->data);
		$this->load->view('user/hash_password', $this->data);
		$this->load->view('footer', $this->data);
	}

	function cron()
	{
		$this->_require_cli_request();

		if ($this->config->item('actions_max_age') == 0) return;

		$oldest_time = (time() - $this->config->item('actions_max_age'));

		$this->db->where('date <', $oldest_time)
			->delete('actions');
	}

	private function _get_line_cli($message, $verification_func = NULL)
	{
		echo "$message: ";

		while ($line = fgets(STDIN)) {
			$line = trim($line);
			if ($verification_func === NULL) {
				return $line;
			}

			if ($verification_func($line)) {
				return $line;
			} else {
				echo "$message: ";
			}
		}
	}

	function add_user()
	{
		$this->_require_cli_request();
		$this->duser->require_implemented("can_register_new_users");

		$error = array();

		$username = $this->_get_line_cli("Username", function($username) {
			if (!$this->muser->valid_username($username)) {
				echo "Invalid username (only up to 32 chars of a-z0-9 are allowed).\n";
				return false;
			} else {
				if (get_instance()->muser->username_exists($username)) {
					echo "Username already exists.\n";
					return false;
				}
			}
			return true;
		});

		$email = $this->_get_line_cli("Email", function($email) {
			if (!$this->muser->valid_email($email)) {
				echo "Invalid email.\n";
				return false;
			}
			return true;
		});

		$password = $this->_get_line_cli("Password", function($password) {
			if (!$password || $password === "") {
				echo "No password supplied.\n";
				return false;
			}
			return true;
		});

		$this->muser->add_user($username, $password, $email, NULL);

		echo "User added\n";
	}

	function delete_user()
	{
		$this->_require_cli_request();
		$this->duser->require_implemented("can_delete_account");

		echo "\nWARNING: Deleting a user will delete ALL their data permanently.\n\n";

		$username = $this->_get_line_cli("Username", function($username) {
			if (get_instance()->muser->username_exists($username)) {
				return true;
			}
			return false;
		});
		$this->muser->delete_user_real($username);
		echo "User removed\n";
	}

}
