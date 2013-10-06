<?php
/*
 * Copyright 2009-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class File extends MY_Controller {

	protected $json_enabled_functions = array(
		"upload_history",
		"do_upload",
		"do_delete",
	);

	function __construct()
	{
		parent::__construct();

		$this->load->model('mfile');

		if (is_cli_client()) {
			$this->var->view_dir = "file_plaintext";
		} else {
			$this->var->view_dir = "file";
		}
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
			echo "  clean_stale_files     Remove files without database entries\n";
			echo "  update_file_metadata  Update filesize and mimetype in database\n";
			exit;
		}
		// Try to guess what the user would like to do.
		$id = $this->uri->segment(1);
		if (!empty($_FILES)) {
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

		$this->load->driver("ddownload");

		// user wants the plain file
		if ($lexer == 'plain') {
			handle_etag($etag);
			$this->ddownload->serveFile($file, $filedata["filename"], "text/plain");
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
			foreach (array("X-WebKit-CSP", "X-Content-Security-Policy", "Content-Security-Policy") as $header_name) {
				header("$header_name: allow 'none'; img-src *; media-src *; font-src *; style-src * 'unsafe-inline'; script-src 'none'; object-src *; frame-src 'none'; ");
			}
			handle_etag($etag);
			$this->ddownload->serveFile($file, $filedata["filename"], $filedata["mimetype"]);
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
		$this->load->driver('cache', array('adapter' => $this->config->item("cache_backend")));
		if (! $cached = $this->cache->get($filedata['hash'].'_'.$lexer)) {
			$cached = array();
			if ($lexer == "rmd") {
				ob_start();

				echo '<div class="code content table markdownrender">'."\n";
				echo '<div class="table-row">'."\n";
				echo '<div class="table-cell">'."\n";
				passthru('perl '.FCPATH.'scripts/Markdown.pl '.escapeshellarg($file), $cached["return_value"]);
				echo '</div></div></div>';

				$cached["output"] = ob_get_contents();
				ob_end_clean();
			} else {
				$cached = $this->_colorify($file, $lexer);
			}

			if ($cached["return_value"] != 0) {
				$ret = $this->_colorify($file, "text");
				$cached["output"] = $ret["output"];
			}
			$this->cache->save($filedata['hash'].'_'.$lexer, $cached, 100);
		}

		if ($cached["return_value"] != 0) {
			$this->data["error_message"] = "<p>Error trying to process the file.
				Either the lexer is unknown or something is broken.
				Falling back to plain text.</p>";
		}

		// Don't use append_output because the output class does too
		// much magic ({elapsed_time} and {memory_usage}).
		// Direct echo puts us on the safe side.
		echo $this->load->view($this->var->view_dir.'/html_header', $this->data, true);
		echo $cached["output"];
		echo $this->load->view($this->var->view_dir.'/html_footer', $this->data, true);
	}

	private function _colorify($file, $lexer)
	{
		$return_value = 0;
		$output = "";
		$lines_to_remove = 0;

		$output .= '<div class="code content table">'."\n";
		$output .= '<div class="highlight"><pre>'."\n";

		ob_start();
		if ($lexer == "ascii") {
			passthru('ansi2html -p < '.escapeshellarg($file), $return_value);
			// Last line is empty
			$lines_to_remove = 1;
		} else {
			passthru('pygmentize -F codetagify -O encoding=guess,outencoding=utf8,stripnl=False -l '.escapeshellarg($lexer).' -f html '.escapeshellarg($file), $return_value);
			// Last 2 items are "</pre></div>" and ""
			$lines_to_remove = 2;
		}
		$buf = ob_get_contents();
		ob_end_clean();


		$buf = explode("\n", $buf);
		$line_count = count($buf);

		for ($i = 1; $i <= $lines_to_remove; $i++) {
			unset($buf[$line_count - $i]);
		}

		foreach ($buf as $key => $line) {
			$line_number = $key + 1;
			if ($key == 0) {
				$line = str_replace("<div class=\"highlight\"><pre>", "", $line);
			}

			// Be careful not to add superflous whitespace here (we are in a <pre>)
			$output .= "<div class=\"table-row\">"
							."<a href=\"#n$line_number\" class=\"linenumber table-cell\">"
								."<span class=\"anchor\" id=\"n$line_number\"> </span>"
							."</a>"
							."<span class=\"line table-cell\">".$line."</span>\n";
			$output .= "</div>";
		}

		$output .= "</pre></div>";
		$output .= "</div>";

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
			$this->muser->require_access("apikey");
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

		if (static_storage("response_type") == "json") {
			return send_json_reply($this->data["urls"]);
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

		if (file_exists(FCPATH.'data/client/latest')) {
			$this->var->latest_client = trim(file_get_contents(FCPATH.'data/client/latest'));
			$this->data['client_link'] = base_url().'data/client/fb-'.$this->var->latest_client.'.tar.gz';
		} else {
			$this->data['client_link'] = false;
		}
		$this->data['client_link_dir'] = base_url().'data/client/';
		$this->data['client_link_deb'] = base_url().'data/client/deb/';
		$this->data['client_link_slackware'] = base_url().'data/client/slackware/';

		if (preg_match('#^https?://(.*?)/.*$#', site_url(), $matches) === 1) {
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

		$this->data['username'] = $this->muser->get_username();

		$repaste_id = $this->input->get("repaste");

		if ($repaste_id) {
			$filedata = $this->mfile->get_filedata($repaste_id);

			if ($filedata !== false && $this->mfile->can_highlight($filedata["mimetype"])) {
				$this->data["textarea_content"] = file_get_contents($this->mfile->file($filedata["hash"]));
			}
		}

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

	function thumbnail()
	{
		$id = $this->uri->segment(3);

		if (!$this->mfile->valid_id($id)) {
			return $this->_non_existent();
		}

		$etag = "$id-thumb";
		handle_etag($etag);

		$thumb = $this->mfile->makeThumb($id, 150, IMAGETYPE_JPEG);

		if ($thumb === false) {
			show_error("Failed to generate thumbnail");
		}

		$filedata = $this->mfile->get_filedata($id);
		if (!$filedata) {
			show_error("Failed to get file data");
		}

		$this->output->set_header("Cache-Control:max-age=31536000, public");
		$this->output->set_header("Expires: ".date("r", time() + 365 * 24 * 60 * 60));
		$this->output->set_content_type("image/jpeg");
		$this->output->set_output($thumb);
	}

	function upload_history_thumbnails()
	{
		$this->muser->require_access();

		$user = $this->muser->get_userid();

		$query = $this->db->query("
			SELECT `id`, `filename`, `mimetype`, `date`, `hash`, `filesize`
			FROM files
			WHERE user = ?
			AND mimetype IN ('image/jpeg', 'image/png', 'image/gif')
			ORDER BY date DESC
			", array($user))->result_array();

		foreach($query as $key => $item) {
			if (!$this->mfile->valid_id($item["id"])) {
				unset($query[$key]);
				continue;
			}

			$filesize = format_bytes($item["filesize"]);
			$dimensions = $this->mfile->image_dimension($this->mfile->file($item["hash"]));
			$upload_date = date("r", $item["date"]);

			$query[$key]["filesize"] = $filesize;
			$query[$key]["tooltip"] = "
				${item["id"]} - $filesize<br>
				$upload_date
				$dimensions - ${item["mimetype"]}<br>
				";
		}

		$this->data["query"] = $query;

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'/upload_history_thumbnails', $this->data);
		$this->load->view('footer', $this->data);
	}

	function upload_history()
	{
		$this->muser->require_access("apikey");

		$user = $this->muser->get_userid();

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

		if (static_storage("response_type") == "json") {
			return send_json_reply($query);
		}

		foreach($query as $key => $item) {
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

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'/upload_history', $this->data);
		$this->load->view('footer', $this->data);
	}

	function do_delete()
	{
		$this->muser->require_access("apikey");

		$ids = $this->input->post("ids");
		$errors = array();
		$deleted = array();
		$deleted_count = 0;
		$total_count = 0;

		if (!$ids || !is_array($ids)) {
			show_error("No IDs specified");
		}

		foreach ($ids as $id) {
			$total_count++;

			if (!$this->mfile->id_exists($id)) {
				$errors[] = array(
					"id" => $id,
					"reason" => "doesn't exist",
				);
				continue;
			}

			if ($this->mfile->delete_id($id)) {
				$deleted[] = $id;
				$deleted_count++;
			} else {
				$errors[] = array(
					"id" => $id,
					"reason" => "unknown error",
				);
			}
		}

		if (static_storage("response_type") == "json") {
			return send_json_reply(array(
				"errors" => $errors,
				"deleted" => $deleted,
				"total_count" => $total_count,
				"deleted_count" => $deleted_count,
			));
		}

		$this->data["errors"] = $errors;
		$this->data["deleted_count"] = $deleted_count;
		$this->data["total_count"] = $total_count;

		$this->load->view('header', $this->data);
		$this->load->view($this->var->view_dir.'/deleted', $this->data);
		$this->load->view('footer', $this->data);
	}

	function delete()
	{
		$this->muser->require_access("apikey");

		if (!is_cli_client()) {
			show_error("Not a listed cli client, please use the history to delete uploads.\n", 403);
		}

		$id = $this->uri->segment(3);
		$this->data["id"] = $id;

		if ($id && !$this->mfile->id_exists($id)) {
			show_error("Unknown ID '$id'.", 404);
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
		// stateful clients get a cookie to claim the ID later
		// don't force them to log in just yet
		if (!stateful_client()) {
			$this->muser->require_access();
		}

		$content = $this->input->post("content");
		$filesize = strlen($content);
		$filename = "stdin";

		if (!$content) {
			show_error("Nothing was pasted, content is empty.", 400);
		}

		if ($filesize > $this->config->item('upload_max_size')) {
			show_error("Error while uploading: File too big", 413);
		}

		$limits = $this->muser->get_upload_id_limits();
		$id = $this->mfile->new_id($limits[0], $limits[1]);
		$hash = md5($content);

		$folder = $this->mfile->folder($hash);
		file_exists($folder) || mkdir ($folder);
		$file = $this->mfile->file($hash);

		file_put_contents($file, $content);
		$this->mfile->add_file($hash, $id, $filename);
		$this->_show_url(array($id), false);
	}

	// Handles uploaded files
	function do_upload()
	{
		// stateful clients get a cookie to claim the ID later
		// don't force them to log in just yet
		if (!stateful_client()) {
			$this->muser->require_access("apikey");
		}

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
			if ($file['error'] !== UPLOAD_ERR_OK) {
				// ERR_OK only for completeness, condition above ignores it
				$errors = array(
					UPLOAD_ERR_OK => "There is no error, the file uploaded with success",
					UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
					UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
					UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
					UPLOAD_ERR_NO_FILE => "No file was uploaded",
					UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
					UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
					UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload",
				);

				$msg = "Unknown error.";

				if (isset($errors[$file['error']])) {
					$msg = $errors[$file['error']];
				} else {
					$msg = "Unknown error code: ".$file['error'].". Please report a bug.";
				}

				show_error("Error while uploading: ".$msg, 400);
			}

			$filesize = filesize($file['tmp_name']);
			if ($filesize > $this->config->item('upload_max_size')) {
				show_error("Error while uploading: File too big", 413);
			}
		}

		foreach ($files as $key => $file) {
			$limits = $this->muser->get_upload_id_limits();
			$id = $this->mfile->new_id($limits[0], $limits[1]);
			$hash = md5_file($file['tmp_name']);

			// work around a curl bug and allow the client to send the real filename base64 encoded
			// TODO: this interface currently sets the same filename for every file if you use multiupload
			$filename = $this->input->post("filename");
			if ($filename !== false) {
				$filename = base64_decode($filename, true);
			}

			// fall back if base64_decode failed
			if ($filename === false) {
				$filename = $file['name'];
			}

			$filename = trim($filename, "\r\n\0\t\x0B");

			$folder = $this->mfile->folder($hash);
			file_exists($folder) || mkdir ($folder);
			$file_path = $this->mfile->file($hash);

			move_uploaded_file($file['tmp_name'], $file_path);
			$this->mfile->add_file($hash, $id, $filename);
			$ids[] = $id;
		}

		$this->_show_url($ids, $extension);
	}

	function claim_id()
	{
		$this->muser->require_access();

		$last_upload = $this->session->userdata("last_upload");

		if ($last_upload === false) {
			show_error("Failed to get last upload data");
		}

		$ids = $last_upload["ids"];
		$errors = array();

		assert(is_array($ids));

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

	function contact()
	{
		$file = FCPATH."data/local/contact-info.php";
		if (file_exists($file)) {
			$this->data["contact_info"] = file_get_contents($file);
		} else {
			$this->data["contact_info"] = '<p>Contact info not available.</p>';
		}

		$this->load->view('header', $this->data);
		$this->load->view('contact', $this->data);
		$this->load->view('footer', $this->data);
	}

	/* Functions below this comment can only be run via the CLI
	 * `php index.php file <function name>`
	 */

	// Removes old files
	function cron()
	{
		if (!$this->input->is_cli_request()) return;

		// 0 age disables age checks
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

	function update_file_metadata()
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
				$mimetype = $this->mfile->mimetype($this->mfile->file($hash));

				$this->db->query("
					UPDATE files
					SET filesize = ?, mimetype = ?
					WHERE hash = ?
					", array($filesize, $mimetype, $hash));
			}
		}
	}
}

# vim: set noet:
