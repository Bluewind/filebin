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
		
		$this->load->helper('form');

		$this->var->view_dir = "user/";
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
					$this->load->view($this->var->view_dir.'header', $this->data);
					$this->load->view($this->var->view_dir.'login_successful', $this->data);
					$this->load->view($this->var->view_dir.'footer', $this->data);
				}
			}
		} else {
			$this->load->view($this->var->view_dir.'header', $this->data);
			$this->load->view($this->var->view_dir.'login', $this->data);
			$this->load->view($this->var->view_dir.'footer', $this->data);
		}
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
