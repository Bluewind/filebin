<?php
/*
 * Copyright 2009-2011 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class File extends CI_Controller {

	var $data = array();
	var $var;

	function __construct()
	{
		parent::__construct();

		$this->load->library('migration');
		if ( ! $this->migration->current()) {
			show_error($this->migration->error_string());
		}

		$old_path = getenv("PATH");
		putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

		mb_internal_encoding('UTF-8');
		$this->load->helper(array('form', 'filebin'));
		$this->load->model('file_mod');
		$this->load->model('muser');

		$this->var->cli_client = false;
		$this->file_mod->var->cli_client =& $this->var->cli_client;
		$this->var->latest_client = false;
		if (file_exists(FCPATH.'data/client/latest')) {
			$this->var->latest_client = trim(file_get_contents(FCPATH.'data/client/latest'));
		}

		$this->var->cli_client = is_cli_client();

		if ($this->var->cli_client) {
			$this->var->view_dir = "file_plaintext";
		} else {
			$this->var->view_dir = "file";
		}

		if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_USER'] && $_SERVER['PHP_AUTH_PW']) {
			if (!$this->muser->login($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				// TODO: better message
				echo "login failed.\n";
				exit;
			}
		}

		$this->data['username'] = $this->muser->get_username();
		$this->data['title'] = "FileBin";
	}

	function index()
	{
		// Try to guess what the user would like to do.
		$id = $this->uri->segment(1);
		if(isset($_FILES['file'])) {
			$this->do_upload();
		} elseif ($id != "file" && $this->file_mod->id_exists($id)) {
			$this->file_mod->download();
		} elseif ($id && $id != "file") {
			$this->file_mod->non_existent();
		} else {
			$this->upload_form();
		}
	}

	function client()
	{
		$this->data['title'] .= ' - Client';
		if ($this->var->latest_client) {
			$this->data['client_link'] = base_url().'data/client/fb-'.$this->var->latest_client.'.tar.gz';
		} else {
			$this->data['client_link'] = false;
		}
		$this->data['client_link_dir'] = base_url().'data/client/';
		$this->data['client_link_deb'] = base_url().'data/client/deb/';
		$this->data['client_link_slackware'] = base_url().'data/client/slackware/';

		if (!$this->var->cli_client) {
			$this->load->view($this->var->view_dir.'/header', $this->data);
		}
		$this->load->view($this->var->view_dir.'/client', $this->data);
		if (!$this->var->cli_client) {
			$this->load->view($this->var->view_dir.'/footer', $this->data);
		}
	}

	function upload_form()
	{
		$this->data['title'] .= ' - Upload';
		$this->data['small_upload_size'] = $this->config->item('small_upload_size');
		$this->data['max_upload_size'] = $this->config->item('upload_max_size');
		$this->data['upload_max_age'] = $this->config->item('upload_max_age')/60/60/24;
		$this->data['contact_me_url'] = $this->config->item('contact_me_url');

		$this->data['username'] = $this->muser->get_username();

		$this->load->view($this->var->view_dir.'/header', $this->data);
		$this->load->view($this->var->view_dir.'/upload_form', $this->data);
		if ($this->var->cli_client) {
			$this->client();
		}
		$this->load->view($this->var->view_dir.'/footer', $this->data);
	}

	// Allow CLI clients to query the server for the maxium filesize so they can
	// stop the upload before wasting time and bandwith
	function get_max_size()
	{
		echo $this->config->item('upload_max_size');
	}

	function upload_history()
	{
		$this->muser->require_access();

		$user = $this->muser->get_userid();

		$this->load->library("MemcacheLibrary");
		if (! $cached = $this->memcachelibrary->get("history_".$this->var->view_dir."_".$user)) {
			$query = array();
			$lengths = array();
			$fields = array("id", "filename", "mimetype", "date", "hash", "filesize");
			$this->data['title'] .= ' - Upload history';
			foreach($fields as $length_key) {
				$lengths[$length_key] = 0;
			}

			$query = $this->db->query("
				SELECT ".implode(",", $fields)."
				FROM files
				WHERE user = ?
				ORDER BY date
				", array($user))->result_array();

			foreach($query as $key => $item) {
				$query[$key]["date"] = date("r", $item["date"]);
				$query[$key]["filesize"] = format_bytes($item["filesize"]);
				if ($this->var->cli_client) {
					// Keep track of longest string to pad plaintext output correctly
					foreach($fields as $length_key) {
						$len = mb_strlen($query[$key][$length_key]);
						if ($len > $lengths[$length_key]) {
							$lengths[$length_key] = $len;
						}
					}
				}
			}

			$this->data["query"] = $query;
			$this->data["lengths"] = $lengths;

			$cached = "";
			$cached .= $this->load->view($this->var->view_dir.'/header', $this->data, true);
			$cached .= $this->load->view($this->var->view_dir.'/upload_history', $this->data, true);
			$cached .= $this->load->view($this->var->view_dir.'/footer', $this->data, true);
			$this->memcachelibrary->set('history_'.$this->var->view_dir."_".$user, $cached, 42);
		}

		echo $cached;
	}

	// Allow users to delete IDs if their password matches the one used when uploading
	function delete()
	{
		$this->muser->require_access();

		$id = $this->uri->segment(3);
		$this->data["title"] .= " - Delete $id";
		$this->data["id"] = $id;

		$process = $this->input->post("process");
		if ($this->var->cli_client) {
			$process = true;
		}

		$this->data["filedata"] = $this->file_mod->get_filedata($id);
		if ($this->data["filedata"]) {
			$this->data["filedata"]["size"] = filesize($this->file_mod->file($this->data["filedata"]["hash"]));
		}

		if ($id && !$this->file_mod->id_exists($id)) {
			$this->output->set_status_header(404);
			$this->data["msg"] = "Unknown ID.";
		} elseif ($process) {
			if ($this->file_mod->delete_id($id)) {
				$this->load->view($this->var->view_dir.'/header', $this->data);
				$this->load->view($this->var->view_dir.'/deleted', $this->data);
				$this->load->view($this->var->view_dir.'/footer', $this->data);
				return;
			} else {
				$this->data["msg"] = "Deletion failed. Do you really own that file?";
			}
		}

		$this->data["can_delete"] = $this->data["filedata"]["user"] == $this->muser->get_userid();

		$this->load->view($this->var->view_dir.'/header', $this->data);
		$this->load->view($this->var->view_dir.'/delete_form', $this->data);
		$this->load->view($this->var->view_dir.'/footer', $this->data);
	}

	// Handle pastes
	function do_paste()
	{
		$content = $this->input->post("content");
		$filesize = strlen($content);
		$filename = "stdin";

		if(!$content) {
			$this->output->set_status_header(400);
			$this->data["msg"] = "Nothing was pasted, content is empty.";
			$this->load->view($this->var->view_dir.'/header', $this->data);
			$this->load->view($this->var->view_dir.'/upload_error', $this->data);
			$this->load->view($this->var->view_dir.'/footer');
			return;
		}

		if ($filesize > $this->config->item('upload_max_size')) {
			$this->output->set_status_header(413);
			$this->load->view($this->var->view_dir.'/header', $this->data);
			$this->load->view($this->var->view_dir.'/too_big');
			$this->load->view($this->var->view_dir.'/footer');
			return;
		}

		$id = $this->file_mod->new_id();
		$hash = md5($content);

		$folder = $this->file_mod->folder($hash);
		file_exists($folder) || mkdir ($folder);
		$file = $this->file_mod->file($hash);

		file_put_contents($file, $content);
		chmod($file, 0600);
		$this->file_mod->add_file($hash, $id, $filename);
		$this->file_mod->show_url($id, false);
	}

	// Handles uploaded files
	function do_upload()
	{
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

			$this->data["msg"] = "Unknown error.";

			if (isset($_FILES["file"])) {
				$this->data["msg"] = $errors[$_FILES['file']['error']];
			}
			$this->load->view($this->var->view_dir.'/header', $this->data);
			$this->load->view($this->var->view_dir.'/upload_error', $this->data);
			$this->load->view($this->var->view_dir.'/footer');
			return;
		}

		$filesize = filesize($_FILES['file']['tmp_name']);
		if ($filesize > $this->config->item('upload_max_size')) {
			$this->output->set_status_header(413);
			$this->load->view($this->var->view_dir.'/header', $this->data);
			$this->load->view($this->var->view_dir.'/too_big');
			$this->load->view($this->var->view_dir.'/footer');
			return;
		}

		$id = $this->file_mod->new_id();
		$hash = md5_file($_FILES['file']['tmp_name']);

		// work around a curl bug and allow the client to send the real filename base64 encoded
		$filename = $this->input->post("filename");
		if ($filename !== false) {
			$filename = trim(base64_decode($filename, true), "\r\n\0\t\x0B");
		}

		// fall back if base64_decode failed
		if ($filename === false) {
			$filename = $_FILES['file']['name'];
		}

		$folder = $this->file_mod->folder($hash);
		file_exists($folder) || mkdir ($folder);
		$file = $this->file_mod->file($hash);

		move_uploaded_file($_FILES['file']['tmp_name'], $file);
		chmod($file, 0600);
		$this->file_mod->add_file($hash, $id, $filename);
		$this->file_mod->show_url($id, $extension);
	}

	function claim_id()
	{
		$this->muser->require_access();

		$last_upload = $this->session->userdata("last_upload");
		$id = $last_upload["id"];

		$filedata = $this->file_mod->get_filedata($id);

		if ($filedata["owner"] != 0) {
			show_error("Someone already owns '$id', can't reassign.");
		}

		$this->file_mod->adopt($id);

		$this->session->unset_userdata("last_upload");

		$this->file_mod->show_url($id, $last_upload["mode"]);
	}

	/* Functions below this comment can only be run via the CLI
	 * `php index.php file <function name>`
	 */

	// Removes old files
	function cron()
	{
		if (!$this->input->is_cli_request()) return;

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

	/* remove files without database entries */
	function clean_stale_files()
	{
		if (!$this->input->is_cli_request()) return;

		$upload_path = $this->config->item("upload_path");
		$outer_dh = opendir($upload_path);

		while (($dir = readdir($outer_dh)) !== false) {
			if (!is_dir($upload_path."/".$dir) || $dir == ".." || $dir == ".") {
				continue;
			}

			$dh = opendir($upload_path."/".$dir);

			$empty = true;
			
			while (($file = readdir($dh)) !== false) {
				if ($file == ".." || $file == ".") {
					continue;
				}

				$query = $this->db->query("SELECT hash FROM files WHERE hash = ? LIMIT 1", array($file))->row_array();

				if (empty($query)) {
					unlink($upload_path."/".$dir."/".$file);
				} else {
					$empty = false;
				}
			}

			closedir($dh);

			if ($empty) {
				rmdir($upload_path."/".$dir);
			}
		}
		closedir($outer_dh);
	}

	function nuke_id()
	{
		if (!$this->input->is_cli_request()) return;

		$id = $this->uri->segment(3);


		$file_data = $this->file_mod->get_filedata($id);

		if (empty($file_data)) {
			echo "unknown id \"$id\"\n";
			return;
		}

		$hash = $file_data["hash"];

		$this->db->query("
			DELETE FROM files
			WHERE hash = ?
			", array($hash));

		unlink($this->file_mod->file($hash));

		echo "removed hash \"$hash\"\n";
	}

	function update_file_sizes()
	{
		if (!$this->input->is_cli_request()) return;

		$chunk = 500;

		$total = $this->db->count_all("files");

		for ($limit = 0; $limit < $total; $limit += $chunk) {
			$query = $this->db->query("
				SELECT hash
				FROM files
				GROUP BY hash
				LIMIT $limit, $chunk
				")->result_array();

			foreach ($query as $key => $item) {
				$hash = $item["hash"];
				$filesize = intval(filesize($this->file_mod->file($hash)));
				$this->db->query("
					UPDATE files
					SET filesize = ?
					WHERE hash = ?
					", array($filesize, $hash));
			}
		}


	}
}

# vim: set noet:
