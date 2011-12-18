<?php
/*
 * Copyright 2009-2011 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class File_mod extends CI_Model {

	function __construct()
	{
		parent::__construct();
	}

	// Returns an unused ID
	// TODO: make threadsafe
	function new_id()
	{
		$id = $this->random_id(3,6);

		if ($this->id_exists($id) || $id == 'file') {
			return $this->new_id();
		} else {
			return $id;
		}
	}

	function id_exists($id)
	{
		if(!$id) {
			return false;
		}

		$sql = '
			SELECT id
			FROM `files`
			WHERE `id` = ?
			LIMIT 1';
		$query = $this->db->query($sql, array($id));

		if ($query->num_rows() == 1) {
			return true;
		} else {
			return false;
		}
	}

	function get_filedata($id)
	{
		$sql = '
			SELECT hash, filename, mimetype, date
			FROM `files`
			WHERE `id` = ?
			LIMIT 1';
		$query = $this->db->query($sql, array($id));

		if ($query->num_rows() == 1) {
			$return = $query->result_array();
			return $return[0];
		} else {
			return false;
		}
	}

	// return the folder in which the file with $hash is stored
	function folder($hash) {
		return $this->config->item('upload_path').'/'.substr($hash, 0, 3);
	}

	// Returns the full path to the file with $hash
	function file($hash) {
		return $this->folder($hash).'/'.$hash;
	}

	function hash_password($password)
	{
		return sha1($this->config->item('passwordsalt').$password);
	}

	// Returns the password submitted by the user
	function get_password()
	{
		$password = $this->input->post('password');
		if ($password !== false && $password !== "") {
			return $this->hash_password($password);
		} elseif (isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_PW'] !== '') {
			return $this->hash_password($_SERVER['PHP_AUTH_PW']);
		}
		return 'NULL';
	}

	// Add a hash to the DB
	// TODO: Should only update not insert; see new_id()
	function add_file($hash, $id, $filename)
	{
		$mimetype = exec("perl ".FCPATH.'scripts/mimetype '.escapeshellarg($filename).' '.escapeshellarg($this->file($hash)));
		$query = $this->db->query('
			INSERT INTO `files` (`hash`, `id`, `filename`, `password`, `date`, `mimetype`)
			VALUES (?, ?, ?, ?, ?, ?)',
			array($hash, $id, $filename, $this->get_password(), time(), $mimetype));
	}

	function show_url($id, $mode)
	{
		$data = array();
		$redirect = false;

		if ($mode) {
			$data['url'] = site_url($id).'/'.$mode;
		} else {
			$data['url'] = site_url($id).'/';

			$filedata = $this->get_filedata($id);
			$file = $this->file($filedata['hash']);
			$type = $filedata['mimetype'];
			$mode = $this->mime2mode($type);

			// If we detected a highlightable file redirect,
			// otherwise show the URL because browsers would just show a DL dialog
			if ($mode) {
				$redirect = true;
			}
		}

		if ($this->var->cli_client) {
			$redirect = false;
		}
		if ($redirect) {
			redirect($data['url'], "location", 303);
		} else {
			$this->load->view($this->var->view_dir.'/header', $data);
			$this->load->view($this->var->view_dir.'/show_url', $data);
			$this->load->view($this->var->view_dir.'/footer', $data);
		}
	}

	function non_existent()
	{
		$data["title"] = "Not Found";
		$this->output->set_status_header(404);
		$this->load->view($this->var->view_dir.'/header', $data);
		$this->load->view($this->var->view_dir.'/non_existent', $data);
		$this->load->view($this->var->view_dir.'/footer', $data);
	}

	// remove old/invalid/broken IDs
	function valid_id($id)
	{
		$filedata = $this->get_filedata($id);
		if (!$filedata) {
			return false;
		}
		$file = $this->file($filedata['hash']);

		if (!file_exists($file)) {
			if (isset($filedata["hash"])) {
				$this->db->query('DELETE FROM files WHERE hash = ?', array($filedata['hash']));
			}
			return false;
		}

		// small files don't expire
		if (filesize($file) <= $this->config->item("small_upload_size")) {
			return true;
		}

		// files older than this should be removed
		$remove_before = (time()-$this->config->item('upload_max_age'));

		if ($filedata["date"] < $remove_before) {
			// if the file has been uploaded multiple times the mtime is the time
			// of the last upload
			if (filemtime($file) < $remove_before) {
				unlink($file);
				$this->db->query('DELETE FROM files WHERE hash = ?', array($filedata['hash']));
			} else {
				$this->db->query('DELETE FROM files WHERE id = ? LIMIT 1', array($id));
			}
			return false;
		}

		return true;
	}

	// download a given ID
	// TODO: make smaller
	function download()
	{
		$data = array();
		$id = $this->uri->segment(1);
		$mode = $this->uri->segment(2);

		$filedata = $this->get_filedata($id);
		$file = $this->file($filedata['hash']);

		if (!$this->valid_id($id)) {
			$this->non_existent();
			return;
		}

		// MODIFIED SINCE SUPPORT -- START
		// helps to keep traffic low when reloading
		$etag = strtolower($filedata["hash"]."-".$filedata["date"]);
		$modified = true;

		// No need to check because different files have different IDs/hashes
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
				$modified = false;
		}

		if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			$oldtag = trim(strtolower($_SERVER['HTTP_IF_NONE_MATCH']), '"');
			if($oldtag == $etag) {
				$modified = false;
			} else {
				$modified = true;
			}
		}

		if (!$modified) {
			header("HTTP/1.1 304 Not Modified");
			header('Etag: "'.$etag.'"');
			exit();
		}
		// MODIFIED SINCE SUPPORT -- END

		$type = $filedata['mimetype'];

		// autodetect the mode for highlighting if the URL contains a / after the ID (/ID/)
		// /ID/mode disables autodetection
		$autodetect_mode = !$mode && substr_count(ltrim($this->uri->uri_string(), "/"), '/') >= 1;

		if ($autodetect_mode) {
			$mode = $this->get_highlight_mode($type, $filedata["filename"]);
		}
		// resolve aliases of modes
		// this is mainly used for compatibility
		$mode = $this->resolve_mode_alias($mode);

		header("Last-Modified: ".date('D, d M Y H:i:s', $filedata["date"])." GMT");
		header('Etag: "'.$etag.'"');

		// create the qr code for /ID/
		if ($mode == "qr") {
			header("Content-disposition: inline; filename=\"".$id."_qr.png\"\n");
			header("Content-Type: image/png\n");
			passthru('qrencode -s 10 -o - '.escapeshellarg(site_url($id).'/'));
			exit();
		}

		// user wants to the the plain file
		if ($mode == 'plain') {
			rangeDownload($file, $filedata["filename"], "text/plain");
			exit();
		}

		// if there is no mimetype mapping we can't highlight it
		$can_highlight = $this->can_highlight($type);

		$filesize_too_big = filesize($file) > $this->config->item('upload_max_text_size');

		if (!$can_highlight || $filesize_too_big || !$mode) {
			rangeDownload($file, $filedata["filename"], $type);
			exit();
		}

		$data['title'] = $filedata['filename'];
		$data['raw_link'] = site_url($id);
		$data['new_link'] = site_url();
		$data['plain_link'] = site_url($id.'/plain');
		$data['auto_link'] = site_url($id).'/';
		$data['rmd_link'] = site_url($id.'/rmd');
		$data['delete_link'] = site_url("file/delete/".$id);

		header("Content-Type: text/html\n");

		$data['current_highlight'] = $mode;

		if (filesize($file) > $this->config->item("small_upload_size")) {
			$data['timeout'] = date("r", $filedata["date"] + $this->config->item("upload_max_age"));
		} else {
			$data['timeout'] = "never";
		}

		echo $this->load->view($this->var->view_dir.'/html_header', $data, true);

		// highlight the file and chache the result
		$this->load->library("MemcacheLibrary");
		if (! $cached = $this->memcachelibrary->get($filedata['hash'].'_'.$mode)) {
			ob_start();
			if ($mode == "rmd") {
				echo '<td class="markdownrender">'."\n";
				passthru('perl '.FCPATH.'scripts/Markdown.pl '.escapeshellarg($file));
			} elseif ($mode == "ascii") {
				echo '<td class="code"><pre class="text">'."\n";
				passthru('perl '.FCPATH.'scripts/ansi2html '.escapeshellarg($file));
				echo "</pre>\n";
			} else {
				echo '<td class="numbers"><pre>';
				// generate line numbers (links)
				passthru('perl -ne \'print "<a href=\"#n$.\" class=\"no\" id=\"n$.\">$.</a>\n"\' '.escapeshellarg($file));
				echo '</pre></td><td class="code">'."\n";
				$this->load->library('geshi');
				$this->geshi->initialize(array('set_language' => $mode, 'set_source' => file_get_contents($file), 'enable_classes' => 'true'));
				echo $this->geshi->output();
			}
			$cached = ob_get_contents();
			ob_end_clean();
			$this->memcachelibrary->set($filedata['hash'].'_'.$mode, $cached, 100);
		}
		echo $cached;

		echo $this->load->view($this->var->view_dir.'/html_footer', $data, true);

		exit();
	}

	private function unused_file($hash)
	{
		$sql = '
			SELECT id
			FROM `files`
			WHERE `hash` = ?
			LIMIT 1';
		$query = $this->db->query($sql, array($hash));

		if ($query->num_rows() == 0) {
			return true;
		} else {
			return false;
		}
	}

	function delete_id($id)
	{
		$filedata = $this->get_filedata($id);
		$password = $this->get_password();

		if ($password == "NULL") {
			return false;
		}

		if(!$this->id_exists($id)) {
			return false;
		}

		$sql = '
			DELETE
			FROM `files`
			WHERE `id` = ?
			AND password = ?
			LIMIT 1';
		$this->db->query($sql, array($id, $password));

		if($this->id_exists($id))  {
			return false;
		}

		if($this->unused_file($filedata['hash'])) {
			unlink($this->file($filedata['hash']));
			@rmdir($this->folder($filedata['hash']));
		}
		return true;
	}

	// Generate a random ID
	private function random_id($min_length, $max_length)
	{
		$random = '';
		$char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$char_list .= "abcdefghijklmnopqrstuvwxyz";
		$char_list .= "1234567890";
		$length = rand()%($max_length-$min_length) + $min_length;

		for($i = 0; $i < $max_length; $i++) {
			if (strlen($random) == $length) break;
			$random .= substr($char_list,(rand()%(strlen($char_list))), 1);
		}
		return $random;
	}

	// Allow certain types to be highlight without doing it automatically
	function can_highlight($type)
	{
		$typearray = array(
			'image/svg+xml',
		);
		if (in_array($type, $typearray)) return true;

		if ($this->mime2mode($type)) return true;

		return false;
	}

	// Return the mode that should be used for highlighting
	function get_highlight_mode($type, $filename)
	{
		$mode = $this->mime2mode($type);

		// filename modes overwrite mime type mappings
		$filename_mode = $this->filename2mode($filename);
		if ($filename_mode) {
			return $filename_mode;
		}

		return $mode;
	}

	// Map MIME types to modes needed for highlighting
	private function mime2mode($type)
	{
		$typearray = array(
		'text/plain' => 'text',
		'text/plain-ascii' => 'ascii',
		'text/x-python' => 'python',
		'text/x-csrc' => 'c',
		'text/x-chdr' => 'c',
		'text/x-c++hdr' => 'c',
		'text/x-c++src' => 'cpp',
		'text/x-patch' => 'diff',
		'text/x-lua' => 'lua',
		'text/x-java' => 'java',
		'text/x-haskell' => 'haskell',
		'text/x-literate-haskell' => 'haskell',
		'text/x-subviewer' => 'bash',
		'text/x-makefile' => 'make',
		#'text/x-log' => 'log',
		'text/html' => 'xml',
		'text/css' => 'css',
		'message/rfc822' => 'email',
		#'image/svg+xml' => 'xml',
		'application/x-perl' => 'perl',
		'application/xml' => 'xml',
		'application/xml-dtd' => "xml",
		'application/xslt+xml' => "xml",
		'application/javascript' => 'javascript',
		'application/smil' => 'ocaml',
		'application/x-desktop' => 'text',
		'application/x-m4' => 'text',
		'application/x-awk' => 'text',
		'application/x-fluid' => 'text',
		'application/x-java' => 'java',
		'application/x-php' => 'php',
		'application/x-ruby' => 'ruby',
		'application/x-shellscript' => 'bash',
		'application/x-x509-ca-cert' => 'text',
		'application/mbox' => 'email',
		'application/x-genesis-rom' => 'text',
		'application/x-applix-spreadsheet' => 'actionscript'
		);
		if (array_key_exists($type, $typearray)) return $typearray[$type];

		if (strpos($type, 'text/') === 0) return 'text';

		# default
		return false;
	}

	// Map special filenames to modes
	private function filename2mode($name)
	{
		$namearray = array(
			'PKGBUILD' => 'bash',
			'.vimrc' => 'vim'
		);
		if (array_key_exists($name, $namearray)) return $namearray[$name];

		return false;
	}

	// Handle mode aliases
	function resolve_mode_alias($alias)
	{
		if ($alias === false) return false;
		$aliasarray = array(
			'py' => 'python',
			'sh' => 'bash',
			's' => 'asm',
			'pl' => 'perl'
		);
		if (array_key_exists($alias, $aliasarray)) return $aliasarray[$alias];

		return $alias;
	}

}

# vim: set noet:
