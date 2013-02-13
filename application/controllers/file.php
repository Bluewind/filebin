<?php
/*
 * Copyright 2009-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class File extends CI_Controller {

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

		$old_path = getenv("PATH");
		putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

		mb_internal_encoding('UTF-8');
		$this->load->helper(array('form', 'filebin'));
		$this->load->model('mfile');
		$this->load->model('muser');

		$this->var->latest_client = false;
		if (file_exists(FCPATH.'data/client/latest')) {
			$this->var->latest_client = trim(file_get_contents(FCPATH.'data/client/latest'));
		}


		if (is_cli_client()) {
			$this->var->view_dir = "file_plaintext";
		} else {
			$this->var->view_dir = "file";
		}

		if (is_cli_client()) {
			$username = $this->input->post("username");
			$password = $this->input->post("password");

			// prefer post parameters if either (username or password) is set
			if ($username === false && $password === false) {
				if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
					$username = $_SERVER['PHP_AUTH_USER'];
					$password = $_SERVER['PHP_AUTH_PW'];
				}
			}

			if ($username !== false && $password !== false) {
				if (!$this->muser->login($username, $password)) {
					// TODO: better message
					echo "login failed.\n";
					exit;
				}
			}
		}

		$this->data['username'] = $this->muser->get_username();
		$this->data['title'] = "FileBin";
	}

	function index()
	{
		if ($this->input->is_cli_request()) {
			echo "php index.php file <function> [arguments]\n";
			echo "\n";
			echo "Functions:\n";
			echo "  cron               Cronjob\n";
			echo "  nuke_id <ID>       Nukes all IDs sharing the same hash\n";
			echo "\n";
			echo "Functions that shouldn't have to be run:\n";
			echo "  clean_stale_files  Remove files without database entries\n";
			echo "  update_file_sizes  Update filesize in database\n";
			exit;
		}
		// Try to guess what the user would like to do.
		$id = $this->uri->segment(1);
		if(!empty($_FILES)) {
			$this->do_upload();
		} elseif ($id != "file" && $this->mfile->id_exists($id)) {
			$this->_download();
		} elseif ($id && $id != "file") {
			$this->_non_existent();
		} else {
			$this->upload_form();
		}
	}

	function _download()
	{
		$id = $this->uri->segment(1);
		$lexer = urldecode($this->uri->segment(2));

		$filedata = $this->mfile->get_filedata($id);
		$file = $this->mfile->file($filedata['hash']);

		if (!$this->mfile->valid_id($id)) {
			$this->_non_existent();
			return;
		}

		// don't allow unowned files to be downloaded
		if ($filedata["user"] == 0) {
			$this->_non_existent();
			return;
		}

		// helps to keep traffic low when reloading
		$etag = $filedata["hash"]."-".$filedata["date"];

		// autodetect the lexer for highlighting if the URL contains a / after the ID (/ID/)
		// /ID/lexer disables autodetection
		$autodetect_lexer = !$lexer && substr_count(ltrim($this->uri->uri_string(), "/"), '/') >= 1;

		if ($autodetect_lexer) {
			$lexer = $this->mfile->autodetect_lexer($filedata["mimetype"], $filedata["filename"]);
		}

		// resolve aliases
		// this is mainly used for compatibility
		$lexer = $this->mfile->resolve_lexer_alias($lexer);

		// create the qr code for /ID/
		if ($lexer == "qr") {
			handle_etag($etag);
			header("Content-disposition: inline; filename=\"".$id."_qr.png\"\n");
			header("Content-Type: image/png\n");
			passthru('qrencode -s 10 -o - '.escapeshellarg(site_url($id).'/'));
			exit();
		}

		// user wants the plain file
		if ($lexer == 'plain') {
			handle_etag($etag);
			rangeDownload($file, $filedata["filename"], "text/plain");
			exit();
		}

		if ($lexer == 'info') {
			$this->_display_info($id);
			return;
		}

		// if there is no mimetype mapping we can't highlight it
		$can_highlight = $this->mfile->can_highlight($filedata["mimetype"]);

		$filesize_too_big = filesize($file) > $this->config->item('upload_max_text_size');

		if (!$can_highlight || $filesize_too_big || !$lexer) {
			// prevent javascript from being executed and forbid frames
			// this should allow us to serve user submitted HTML content without huge security risks
			foreach (array("X-WebKit-CSP", "X-Content-Security-Policy") as $header_name) {
				header("$header_name: allow 'none'; img-src *; media-src *; font-src *; style-src * 'unsafe-inline'; script-src 'none'; object-src *; frame-src 'none'; ");
			}
			handle_etag($etag);
			rangeDownload($file, $filedata["filename"], $filedata["mimetype"]);
			exit();
		}

		$this->data['title'] = htmlspecialchars($filedata['filename']);
		$this->data['id'] = $id;

		header("Content-Type: text/html\n");

		$this->data['current_highlight'] = htmlspecialchars($lexer);
		$this->data['timeout'] = $this->mfile->get_timeout_string($id);
		$this->data['lexers'] = $this->mfile->get_lexers();
		$this->data['filedata'] = $filedata;

		// highlight the file and chache the result
		$this->load->library("MemcacheLibrary");
		if (! $cached = $this->memcachelibrary->get($filedata['hash'].'_'.$lexer)) {
			$cached = array();
			if ($lexer == "rmd") {
				ob_start();

				echo '<table class="content"><tr>';
				echo '<td class="markdownrender">'."\n";
				passthru('perl '.FCPATH.'scripts/Markdown.pl '.escapeshellarg($file), $cached["return_value"]);

				$cached["output"] = ob_get_contents();
				ob_end_clean();
			} elseif ($lexer == "ascii") {
				ob_start();

				echo '<table class="content"><tr>';
				echo '<td class="code"><pre class="text">'."\n";
				passthru('perl '.FCPATH.'scripts/ansi2html '.escapeshellarg($file), $cached["return_value"]);
				echo "</pre>\n";

				$cached["output"] = ob_get_contents();
				ob_end_clean();
			} else {
				$cached = $this->_pygmentize($file, $lexer);
			}

			if ($cached["return_value"] != 0) {
				$ret = $this->_pygmentize($file, "text");
				$cached["output"] = $ret["output"];
			}
			$this->memcachelibrary->set($filedata['hash'].'_'.$lexer, $cached, 100);
		}

		if ($cached["return_value"] != 0) {
			$this->data["error_message"] = "<p>Error trying to process the file.
				Either the lexer is unknown or something is broken.
				Falling back to plain text.</p>";
		}

		$this->load->view($this->var->view_dir.'/html_header', $this->data);
		$this->output->append_output($cached["output"]);
		$this->load->view($this->var->view_dir.'/html_footer', $this->data);
	}

	private function _pygmentize($file, $lexer)
	{
		$return_value = 0;

		ob_start();

		echo '<table class="content"><tr>';
		echo '<td class="numbers"><pre>';
		// generate line numbers (links)
		passthru('perl -ne \'print "<a href=\"#n$.\" ><span class=\"anchor\" id=\"n$.\"> </span>$.</a>\n"\' '.escapeshellarg($file), $return_value);
		echo '</pre></td><td class="code">'."\n";
		passthru('pygmentize -F codetagify -O encoding=guess,outencoding=utf8 -l '.escapeshellarg($lexer).' -f html '.escapeshellarg($file), $return_value);

		$output = ob_get_contents();
		ob_end_clean();

		return array(
			"return_value" => $return_value,
			"output" => $output
		);
	}

	function _display_info($id)
	{
		$this->data["title"] .= " - Info $id";
		$this->data["filedata"] = $this->mfile->get_filedata($id);
		$this->data["id"] = $id;
		$this->data['timeout'] = $this->mfile->get_timeout_string($id);

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'/file_info', $this->data);
		$this->load->view('footer', $this->data);
	}

	function _non_existent()
	{
		$this->data["title"] .= " - Not Found";
		$this->output->set_status_header(404);
		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'/non_existent', $this->data);
		$this->load->view('footer', $this->data);
	}

	function _show_url($ids, $lexer)
	{
		$redirect = false;

		if (!$this->muser->logged_in()) {
			$this->muser->require_session();
			// keep the upload but require the user to login
			$this->session->set_userdata("last_upload", array(
				"ids" => $ids,
				"lexer" => $lexer
			));
			$this->session->set_flashdata("uri", "file/claim_id");
			$this->muser->require_access();
		}

		foreach ($ids as $id) {
			if ($lexer) {
				$this->data['urls'][] = site_url($id).'/'.$lexer;
			} else {
				$this->data['urls'][] = site_url($id).'/';

				if (count($ids) == 1) {
					$filedata = $this->mfile->get_filedata($id);
					$file = $this->mfile->file($filedata['hash']);
					$type = $filedata['mimetype'];
					$lexer = $this->mfile->should_highlight($type);

					// If we detected a highlightable file redirect,
					// otherwise show the URL because browsers would just show a DL dialog
					if ($lexer) {
						$redirect = true;
					}
				}
			}
		}

		if (is_cli_client()) {
			$redirect = false;
		}

		if ($redirect && count($ids) == 1) {
			redirect($this->data['urls'][0], "location", 303);
		} else {
			$this->load->view('header', $this->data);
			$this->load->view($this->var->view_dir.'/show_url', $this->data);
			$this->load->view('footer', $this->data);
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

		if(preg_match('#^https?://(.*?)/.*$#', site_url(), $matches) === 1) {
			$this->data["domain"] = $matches[1];
		} else {
			$this->data["domain"] = "unknown domain";
		}

		if (!is_cli_client()) {
			$this->load->view('header', $this->data);
		}
		$this->load->view($this->var->view_dir.'/client', $this->data);
		if (!is_cli_client()) {
			$this->load->view('footer', $this->data);
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

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'/upload_form', $this->data);
		if (is_cli_client()) {
			$this->client();
		}
		$this->load->view('footer', $this->data);
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

			// key: database field name; value: display name
			$fields = array(
				"id" => "ID",
				"filename" => "Filename",
				"mimetype" => "Mimetype",
				"date" => "Date",
				"hash" => "Hash",
				"filesize" => "Size"
			);

			$this->data['title'] .= ' - Upload history';
			foreach($fields as $length_key => $value) {
				$lengths[$length_key] = mb_strlen($value);
			}

			$order = is_cli_client() ? "ASC" : "DESC";

			$query = $this->db->query("
				SELECT ".implode(",", array_keys($fields))."
				FROM files
				WHERE user = ?
				ORDER BY date $order
				", array($user))->result_array();

			foreach($query as $key => $item) {
				$query[$key]["date"] = date("r", $item["date"]);
				$query[$key]["filesize"] = format_bytes($item["filesize"]);
				if (is_cli_client()) {
					// Keep track of longest string to pad plaintext output correctly
					foreach($fields as $length_key => $value) {
						$len = mb_strlen($query[$key][$length_key]);
						if ($len > $lengths[$length_key]) {
							$lengths[$length_key] = $len;
						}
					}
				}
			}

			$total_size = $this->db->query("
				SELECT sum(filesize) sum
				FROM (
					SELECT filesize
					FROM files
					WHERE user = ?
					GROUP BY hash
				) sub
				", array($user))->row_array();

			$this->data["query"] = $query;
			$this->data["lengths"] = $lengths;
			$this->data["fields"] = $fields;
			$this->data["total_size"] = format_bytes($total_size["sum"]);

			$cached = "";
			$cached .= $this->load->view('header', $this->data, true);
			$cached .= $this->load->view($this->var->view_dir.'/upload_history', $this->data, true);
			$cached .= $this->load->view('footer', $this->data, true);

			// disable for now. reenable if it causes too much load
			//$this->memcachelibrary->set('history_'.$this->var->view_dir."_".$user, $cached, 42);
		}

		echo $cached;
	}

	function do_delete()
	{
		$this->muser->require_access();

		$ids = $this->input->post("ids");
		$errors = array();
		$msgs = array();
		$deleted_count = 0;
		$total_count = 0;

		if (!$ids) {
			show_error("No IDs specified");
		}

		foreach ($ids as $id) {
			$total_count++;

			if (!$this->mfile->id_exists($id)) {
				$errors[] = "'$id' didn't exist anymore.";
				continue;
			}

			if ($this->mfile->delete_id($id)) {
				$msgs[] = "'$id' has been removed.";
				$deleted_count++;
			} else {
				$errors[] = "'$id' couldn't be deleted.";
			}
		}

		$this->data["errors"] = $errors;
		$this->data["msgs"] = $msgs;
		$this->data["deleted_count"] = $deleted_count;
		$this->data["total_count"] = $total_count;

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'/deleted', $this->data);
		$this->load->view('footer', $this->data);
	}

	function delete()
	{
		$this->muser->require_access();

		if (!is_cli_client()) {
			echo "Not a listed cli client, please use the history to delete uploads.\n";
			return;
		}

		$id = $this->uri->segment(3);
		$this->data["id"] = $id;

		if ($id && !$this->mfile->id_exists($id)) {
			$this->output->set_status_header(404);
			echo "Unknown ID '$id'.\n";
			return;
		}

		if ($this->mfile->delete_id($id)) {
			echo "$id has been deleted.\n";
		} else {
			echo "Deletion failed. Do you really own that file?\n";
		}
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
			$this->load->view('header', $this->data);
			$this->load->view($this->var->view_dir.'/upload_error', $this->data);
			$this->load->view('footer');
			return;
		}

		if ($filesize > $this->config->item('upload_max_size')) {
			$this->output->set_status_header(413);
			$this->load->view('header', $this->data);
			$this->load->view($this->var->view_dir.'/too_big');
			$this->load->view('footer');
			return;
		}

		$id = $this->mfile->new_id();
		$hash = md5($content);

		$folder = $this->mfile->folder($hash);
		file_exists($folder) || mkdir ($folder);
		$file = $this->mfile->file($hash);

		file_put_contents($file, $content);
		chmod($file, 0600);
		$this->mfile->add_file($hash, $id, $filename);
		$this->_show_url(array($id), false);
	}

	// Handles uploaded files
	function do_upload()
	{
		$ids = array();

		$extension = $this->input->post('extension');

		$files = getNormalizedFILES();

		if (empty($files)) {
			show_error("No file was uploaded or unknown error occured.");
		}

		// Check for errors before doing anything
		// First error wins and is displayed, these shouldn't happen that often anyway.
		foreach ($files as $key => $file) {
			// getNormalizedFILES() removes any file with error == 4
			if ($file['error'] !== 0) {
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

				if (isset($file)) {
					$this->data["msg"] = $errors[$file['error']];
				}
				$this->load->view('header', $this->data);
				$this->load->view($this->var->view_dir.'/upload_error', $this->data);
				$this->load->view('footer');
				return;
			}

			$filesize = filesize($file['tmp_name']);
			if ($filesize > $this->config->item('upload_max_size')) {
				$this->output->set_status_header(413);
				$this->load->view('header', $this->data);
				$this->load->view($this->var->view_dir.'/too_big');
				$this->load->view('footer');
				return;
			}
		}

		foreach ($files as $key => $file) {
			$id = $this->mfile->new_id();
			$hash = md5_file($file['tmp_name']);

			// work around a curl bug and allow the client to send the real filename base64 encoded
			// TODO: this interface currently sets the same filename for every file if you use multiupload
			$filename = $this->input->post("filename");
			if ($filename !== false) {
				$filename = trim(base64_decode($filename, true), "\r\n\0\t\x0B");
			}

			// fall back if base64_decode failed
			if ($filename === false) {
				$filename = $file['name'];
			}

			$folder = $this->mfile->folder($hash);
			file_exists($folder) || mkdir ($folder);
			$file_path = $this->mfile->file($hash);

			move_uploaded_file($file['tmp_name'], $file_path);
			chmod($file_path, 0600);
			$this->mfile->add_file($hash, $id, $filename);
			$ids[] = $id;
		}

		$this->_show_url($ids, $extension);
	}

	function claim_id()
	{
		$this->muser->require_access();

		$last_upload = $this->session->userdata("last_upload");
		$ids = $last_upload["ids"];
		$errors = array();

		foreach ($ids as $key => $id) {
			$filedata = $this->mfile->get_filedata($id);

			if ($filedata["user"] != 0) {
				$errors[] = $id;
			}

			$this->mfile->adopt($id);
		}

		if (!empty($errors)) {
			show_error("Someone already owns '".implode(", ", $errors)."', can't reassign.");
		}

		$this->session->unset_userdata("last_upload");

		$this->_show_url($ids, $last_upload["lexer"]);
	}

	/* Functions below this comment can only be run via the CLI
	 * `php index.php file <function name>`
	 */

	// Removes old files
	function cron()
	{
		if (!$this->input->is_cli_request()) return;

		if ($this->config->item('upload_max_age') == 0) return;

		$oldest_time = (time() - $this->config->item('upload_max_age'));
		$oldest_session_time = (time() - $this->config->item("sess_expiration"));

		$small_upload_size = $this->config->item('small_upload_size');

		$query = $this->db->query('
			SELECT hash, id, user
			FROM files
			WHERE date < ? OR (user = 0 AND date < ?)',
				array($oldest_time, $oldest_session_time));

		foreach($query->result_array() as $row) {
			$file = $this->mfile->file($row['hash']);
			if (!file_exists($file)) {
				$this->db->query('DELETE FROM files WHERE id = ? LIMIT 1', array($row['id']));
				continue;
			}

			if ($row["user"] == 0 || filesize($file) > $small_upload_size) {
				if (filemtime($file) < $oldest_time) {
					unlink($file);
					$this->db->query('DELETE FROM files WHERE hash = ?', array($row['hash']));
				} else {
					$this->db->query('DELETE FROM files WHERE id = ? LIMIT 1', array($row['id']));
					if ($this->mfile->stale_hash($row["hash"])) {
						unlink($file);
					}
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


		$file_data = $this->mfile->get_filedata($id);

		if (empty($file_data)) {
			echo "unknown id \"$id\"\n";
			return;
		}

		$hash = $file_data["hash"];

		$this->db->query("
			DELETE FROM files
			WHERE hash = ?
			", array($hash));

		unlink($this->mfile->file($hash));

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
				$filesize = intval(filesize($this->mfile->file($hash)));
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
