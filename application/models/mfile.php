<?php
/*
 * Copyright 2009-2011 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under GPLv3
 * (see COPYING for full license text)
 *
 */

class Mfile extends CI_Model {

	function __construct()
	{
		parent::__construct();
		$this->load->model("muser");
	}

	// Returns an unused ID
	function new_id()
	{
		$id = random_alphanum(3,6);

		if ($this->id_exists($id) || $id == 'file' || $id == 'user') {
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

	public function stale_hash($hash)
	{
		return $this->unused_file($hash);
	}

	function get_filedata($id)
	{
		$sql = '
			SELECT hash, filename, mimetype, date, user, filesize
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

	// Add a hash to the DB
	function add_file($hash, $id, $filename)
	{
		$userid = $this->muser->get_userid();

		$mimetype = exec("perl ".FCPATH.'scripts/mimetype '.escapeshellarg($filename).' '.escapeshellarg($this->file($hash)));
		$filesize = filesize($this->file($hash));
		$query = $this->db->query('
			INSERT INTO `files` (`hash`, `id`, `filename`, `user`, `date`, `mimetype`, `filesize`)
			VALUES (?, ?, ?, ?, ?, ?, ?)',
			array($hash, $id, $filename, $userid, time(), $mimetype, $filesize));
	}

	function adopt($id)
	{
		$userid = $this->muser->get_userid();

		$this->db->query("
			UPDATE files
			SET user = ?
			WHERE id = ?
			", array($userid, $id));
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

	function get_timeout_string($id)
	{
		$filedata = $this->get_filedata($id);
		$file = $this->file($filedata["hash"]);

		if (filesize($file) > $this->config->item("small_upload_size")) {
			return date("r", $filedata["date"] + $this->config->item("upload_max_age"));
		} else {
			return "unknown";
		}
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
		$this->muser->require_access();
		$filedata = $this->get_filedata($id);
		$userid = $this->muser->get_userid();

		if(!$this->id_exists($id)) {
			return false;
		}

		$sql = '
			DELETE
			FROM `files`
			WHERE `id` = ?
			AND user = ?
			LIMIT 1';
		$this->db->query($sql, array($id, $userid));

		if($this->id_exists($id))  {
			return false;
		}

		if($this->unused_file($filedata['hash'])) {
			unlink($this->file($filedata['hash']));
			@rmdir($this->folder($filedata['hash']));
		}
		return true;
	}

	function should_highlight($type)
	{
		if ($this->mime2mode($type)) return true;

		return false;
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
		'text/x-scheme' => 'scheme',
		'text/x-makefile' => 'make',
		#'text/x-log' => 'log',
		'text/html' => 'xml',
		'text/css' => 'css',
		'text/x-ocaml' => 'ocaml',
		'text/x-tcl' => 'tcl',
		'message/rfc822' => 'text',
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
		'application/mbox' => 'text',
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
