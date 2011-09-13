<?php
/*
 * Copyright 2009-2011 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class File extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		mb_internal_encoding('UTF-8');
		$this->load->helper(array('form', 'filebin'));
		$this->load->model('file_mod');
		$this->var->cli_client = false;
		$this->file_mod->var->cli_client =& $this->var->cli_client;
		$this->var->latest_client = false;
		if (file_exists(FCPATH.'data/client/latest')) {
			$this->var->latest_client = trim(file_get_contents(FCPATH.'data/client/latest'));
		}

		// official client uses "fb-client/$version" as useragent
		$clients = array("fb-client", "libcurl", "pyfb");
		foreach ($clients as $client) {
			if (strpos($_SERVER['HTTP_USER_AGENT'], $client) !== false) {
				$this->var->cli_client = true;
				break;
			}
		}

		if ($this->var->cli_client) {
			$this->var->view_dir = "file_plaintext";
		} else {
			$this->var->view_dir = "file";
		}
	}

	function index()
	{
		// Try to guess what the user would like to do.
		// File uploads should be checked first because they are usually big and
		// take quite some time to upload.
		$id = $this->uri->segment(1);
		if(isset($_FILES['file'])) {
			$this->do_upload();
		} elseif ($this->input->post('content')) {
			$this->do_paste();
		} elseif ($id != "file" && $this->file_mod->id_exists($id)) {
			$this->file_mod->download();
		} elseif ($id && $id != "file") {
			$this->file_mod->non_existent();
		} else {
			$this->upload_form();
		}
	}

	function client($load_header = true)
	{
		$data['title'] = 'Client';
		if ($this->var->latest_client) {
			$data['client_link'] = base_url().'data/client/fb-'.$this->var->latest_client.'.tar.gz';
		} else {
			$data['client_link'] = "none";
		}
		$data['client_link_dir'] = base_url().'data/client/';
		$data['client_link_deb'] = base_url().'data/client/deb/';
		$data['client_link_slackware'] = base_url().'data/client/slackware/';

		if ($load_header) {
			$this->load->view($this->var->view_dir.'/header', $data);
		}
		$this->load->view($this->var->view_dir.'/client', $data);
		if ($load_header) {
			$this->load->view($this->var->view_dir.'/footer', $data);
		}
	}

	function upload_form()
	{
		$data = array();
		$data['title'] = 'Upload';
		$data['small_upload_size'] = $this->config->item('small_upload_size');
		$data['max_upload_size'] = $this->config->item('upload_max_size');
		$data['upload_max_age'] = $this->config->item('upload_max_age')/60/60/24;
		$data['contact_me_url'] = $this->config->item('contact_me_url');

		$this->load->view($this->var->view_dir.'/header', $data);
		$this->load->view($this->var->view_dir.'/upload_form', $data);
		if ($this->var->cli_client) {
			$this->client(false);
		}
		$this->load->view($this->var->view_dir.'/footer', $data);
	}

	// Allow CLI clients to query the server for the maxium filesize so they can
	// stop the upload before wasting time and bandwith
	function get_max_size()
	{
		echo $this->config->item('upload_max_size');
	}

	function upload_history()
	{
		$password = $this->file_mod->get_password();

		$this->load->library("MemcacheLibrary");
		if (! $cached = $this->memcachelibrary->get("history_".$this->var->view_dir."_".$password)) {
			$data = array();
			$query = array();
			$lengths = array();
			$fields = array("id", "filename", "mimetype", "date", "hash");
			$data['title'] = 'Upload history';
			foreach($fields as $length_key) {
				$lengths[$length_key] = 0;
			}

			if ($password != "NULL") {
				$query = $this->db->query("
					SELECT ".implode(",", $fields)."
					FROM files
					WHERE password = ?
					ORDER BY date
					", array($password))->result_array();
			}

			foreach($query as $key => $item) {
				$query[$key]["date"] = date("r", $item["date"]);
				// Keep track of longest string to pad plaintext output correctly
				foreach($fields as $length_key) {
					$len = mb_strlen($query[$key][$length_key]);
					if ($len > $lengths[$length_key]) {
						$lengths[$length_key] = $len;
					}
				}
			}

			$data["query"] = $query;
			$data["lengths"] = $lengths;

			$cached = "";
			$cached .= $this->load->view($this->var->view_dir.'/header', $data, true);
			$cached .= $this->load->view($this->var->view_dir.'/upload_history', $data, true);
			$cached .= $this->load->view($this->var->view_dir.'/footer', $data, true);
			$this->memcachelibrary->set('history_'.$this->var->view_dir."_".$password, $cached, 42);
		}

		echo $cached;
	}

	// Allow users to delete IDs if their password matches the one used when uploading
	function delete()
	{
		$data = array();
		$id = $this->uri->segment(3);
		$password = $this->file_mod->get_password();
		$data["title"] = "Delete";
		$data["id"] = $id;
		if ($id && !$this->file_mod->id_exists($id)) {
			$data["msg"] = "Unkown ID.";
		} elseif ($password != "NULL") {
			if ($this->file_mod->delete_id($id)) {
				$this->load->view($this->var->view_dir.'/header', $data);
				$this->load->view($this->var->view_dir.'/deleted', $data);
				$this->load->view($this->var->view_dir.'/footer', $data);
				return;
			} else {
				$data["msg"] = "Deletion failed. Is the password correct?";
			}
		} else {
			if ($this->var->cli_client) {
				$data["msg"] = "No password supplied.";
			}
		}
		$this->load->view($this->var->view_dir.'/header', $data);
		$this->load->view($this->var->view_dir.'/delete_form', $data);
		$this->load->view($this->var->view_dir.'/footer', $data);
	}

	// Handles uploaded files
	function do_upload()
	{
		$data = array();

		if ($this->uri->segment(3)) {
			$this->var->cli_client = true;
			$this->var->view_dir = "file_plaintext";
		}

		$extension = $this->input->post('extension');
		if(!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
			$this->output->set_status_header(400);
			$errors = array(
				0=>"There is no error, the file uploaded with success",
				1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
				2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
				3=>"The uploaded file was only partially uploaded",
				4=>"No file was uploaded",
				6=>"Missing a temporary folder"
			);
			$data["msg"] = $errors[$_FILES['file']['error']];
			$this->load->view($this->var->view_dir.'/header', $data);
			$this->load->view($this->var->view_dir.'/upload_error', $data);
			$this->load->view($this->var->view_dir.'/footer');
			return;
		}

		$filesize = filesize($_FILES['file']['tmp_name']);
		if ($filesize > $this->config->item('upload_max_size')) {
			$this->output->set_status_header(413);
			$this->load->view($this->var->view_dir.'/header', $data);
			$this->load->view($this->var->view_dir.'/too_big');
			$this->load->view($this->var->view_dir.'/footer');
			return;
		}

		$id = $this->file_mod->new_id();
		$hash = md5_file($_FILES['file']['tmp_name']);
		$filename = $_FILES['file']['name'];
		$folder = $this->file_mod->folder($hash);
		file_exists($folder) || mkdir ($folder);
		$file = $this->file_mod->file($hash);

		move_uploaded_file($_FILES['file']['tmp_name'], $file);
		chmod($file, 0600);
		$this->file_mod->add_file($hash, $id, $filename);
		$this->file_mod->show_url($id, $extension);
	}

	// Removes old files
	function cron()
	{
		if ($this->config->item('upload_max_age') == 0) return;

		$oldest_time = (time()-$this->config->item('upload_max_age'));
		$small_upload_size = $this->config->item('small_upload_size');
		$query = $this->db->query('SELECT hash, id FROM files WHERE date < ?',
			array($oldest_time));

		foreach($query->result_array() as $row) {
			$file = $this->file_mod->file($row['hash']);
			if (!file_exists($file)) {
				$this->db->query('DELETE FROM files WHERE id = ? LIMIT 1', array($row['id']));
				continue;
			}

			if (filesize($file) > $small_upload_size) {
				if (filemtime($file) < $oldest_time) {
					unlink($file);
					$this->db->query('DELETE FROM files WHERE hash = ?', array($row['hash']));
				} else {
					$this->db->query('DELETE FROM files WHERE id = ? LIMIT 1', array($row['id']));
				}
			}
		}
	}
}

# vim: set noet:
