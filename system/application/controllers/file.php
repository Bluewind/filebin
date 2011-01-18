<?php
/*
 * Copyright 2009-2010 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class File extends Controller {

	function __construct()
	{
		parent::Controller();
		$this->load->helper(array('form', 'filebin'));
		$this->load->model('file_mod');
		$this->var->cli_client = false;
		$this->file_mod->var->cli_client =& $this->var->cli_client;
		$this->var->latest_client = trim(file_get_contents(FCPATH.'data/client/latest'));

		// official client uses "fb-client/$version" as useragent
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'fb-client') !== false) {
			$client_version = substr($_SERVER['HTTP_USER_AGENT'], 10);
			if ($this->var->latest_client != $client_version)  {
				echo "Your are using an old client version. Latest is ".$this->var->latest_client."\n";
			}
			$this->var->cli_client = "fb-client";
		} elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'libcurl') !== false) {
			$this->var->cli_client = "curl";
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
		} elseif ($this->var->cli_client) {
			die("No upload or unknown ID requested.\n");
		} elseif ($id && $id != "file") {
			$this->file_mod->non_existent();
		} else {
			$this->upload_form();
		}
	}

	function client()
	{
		$data['title'] = 'Client';
		$data['client_link'] = base_url().'data/client/fb-'.$this->var->latest_client.'.tar.gz';
		$data['client_link_dir'] = base_url().'data/client/';
		$data['client_link_deb'] = base_url().'data/client/deb/';
		$data['client_link_slackware'] = base_url().'data/client/slackware/';

		$this->load->view('file/header', $data);
		$this->load->view('file/client', $data);
		$this->load->view('file/footer', $data);
	}

	function upload_form()
	{
		$data = array();
		$data['title'] = 'Upload';
		$data['small_upload_size'] = $this->config->item('small_upload_size');
		$data['max_upload_size'] = $this->config->item('upload_max_size');

		$this->load->view('file/header', $data);
		$this->load->view('file/upload_form', $data);
		$this->load->view('file/footer', $data);
	}

	// Allow CLI clients to query the server for the maxium filesize so they can
	// stop the upload before wasting time and bandwith
	function get_max_size()
	{
		echo $this->config->item('upload_max_size');
	}

	// Allow users to delete IDs if their password matches the one used when uploading
	function delete()
	{
		$data = array();
		$id = $this->uri->segment(3);
		$password = $this->file_mod->get_password();
		$data["title"] = "Delete";
		$data["id"] = $id;
		if ($password != "NULL") {
			if ($this->file_mod->delete_id($id)) {
				if ($this->var->cli_client) {
					echo $id." deleted\n";
					die();
				} else {
					$this->load->view('file/header', $data);
					$this->load->view('file/deleted', $data);
					$this->load->view('file/footer', $data);
					return;
				}
			} else {
				if ($this->var->cli_client) {
					echo 'Couldn\'t delete '.$id."\n";
					die();
				} else {
					$data["msg"] = "Deletion failed. Is the password correct?";
				}
			}
		}
		if ($this->var->cli_client) {
			die();
		} else {
			$this->load->view('file/header', $data);
			$this->load->view('file/delete_form', $data);
			$this->load->view('file/footer', $data);
		}
	}

	// Take the content from post instead of a file
	// support textareas on the upload form
	// XXX: This requires users of suhosin to adjust maxium post and request size
	// TODO: merge with do_upload()
	// XXX: this is too vulnerable to bots
	function do_paste()
	{
		// FIXME: disable until bot problem is really fixed
		return $this->upload_form();

		$data = array();
		$content = $this->input->post('content')."\n";
		$extension = $this->input->post('extension');
		// Try to filter spambots
		if ($this->input->post("email") != "") return;

		// prevent empty pastes from the upload form
		if($content === "\n") {
			$this->upload_form();
			return;
		}
		// TODO: Display nice error for cli clients
		if(strlen($content) > $this->config->item('upload_max_size')) {
			$this->load->view('file/header', $data);
			$this->load->view('file/too_big');
			$this->load->view('file/footer');
			return;
		}

		$id = $this->file_mod->new_id();
		$hash = md5($content);
		$folder = $this->file_mod->folder($hash);
		file_exists($folder) || mkdir ($folder);
		$file = $this->file_mod->file($hash);

		file_put_contents($file, $content);
		chmod($file, 0600);
		$this->file_mod->add_file($hash, $id, 'stdin');
		$this->file_mod->show_url($id, $extension);
	}

	// Handles uploaded files
	// TODO: merge with do_paste()
	function do_upload()
	{
		$data = array();
		$extension = $this->input->post('extension');
		// TODO: Display nice error for cli clients
		if(!isset($_FILES['file'])) {
			$this->load->view('file/header', $data);
			$this->load->view('file/upload_error');
			$this->load->view('file/footer');
			return;
		}
		if ($_FILES['file']['error'] !== 0) {
			$this->upload_form();
			return;
		}
		$filesize = filesize($_FILES['file']['tmp_name']);
		// TODO: Display nice error for cli clients
		if ($filesize > $this->config->item('upload_max_size')) {
			$this->load->view('file/header', $data);
			$this->load->view('file/too_big');
			$this->load->view('file/footer');
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
