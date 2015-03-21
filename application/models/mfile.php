<?php
/*
 * Copyright 2009-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
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
	function new_id($min = 3, $max = 6)
	{
		static $id_blacklist = NULL;

		if ($id_blacklist == NULL) {
			// This prevents people from being unable to access their uploads
			// because of URL rewriting
			$id_blacklist = scandir(FCPATH);
			$id_blacklist[] = "file";
			$id_blacklist[] = "user";
		}

		$max_tries = 100;

		for ($try = 0; $try < $max_tries; $try++) {
			$id = random_alphanum($min, $max);

			if ($this->id_exists($id) || in_array($id, $id_blacklist)) {
				continue;
			}

			return $id;
		}

		throw new \exceptions\PublicApiException("file/new_id-try-limit", "Failed to find unused ID after $max_tries tries");
	}

	function id_exists($id)
	{
		if (!$id) {
			return false;
		}

		$query = $this->db->select('id')
			->from('files')
			->where('id', $id)
			->limit(1)
			->get();

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
		$query = $this->db
			->select('id, hash, filename, mimetype, date, user, filesize')
			->from('files')
			->where('id', $id)
			->limit(1)
			->get();

		if ($query->num_rows() > 0) {
			return $query->row_array();
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

		$mimetype = mimetype($this->file($hash));

		$filesize = filesize($this->file($hash));
		$this->db->insert("files", array(
			"id" => $id,
			"hash" => $hash,
			"filename" => $filename,
			"date" => time(),
			"user" => $userid,
			"mimetype" => $mimetype,
			"filesize" => $filesize,
		));
	}

	function adopt($id)
	{
		$userid = $this->muser->get_userid();

		$this->db->set(array('user' => $userid ))
			->where('id', $id)
			->where('user', 0)
			->update('files');
		return $this->db->affected_rows();
	}

	// remove old/invalid/broken IDs
	function valid_id($id)
	{
		$filedata = $this->get_filedata($id);
		if (!$filedata) {
			return false;
		}

		$config = array(
			"upload_max_age" => $this->config->item("upload_max_age"),
			"small_upload_size" => $this->config->item("small_upload_size"),
			"sess_expiration" => $this->config->item("sess_expiration"),
		);

		return \service\files::valid_id($filedata, $config, $this, time());
	}

	public function file_exists($file)
	{
		return file_exists($file);
	}

	public function filemtime($file)
	{
		return filemtime($file);
	}

	public function filesize($file)
	{
		return filesize($file);
	}

	public function get_timeout($id)
	{
		$filedata = $this->get_filedata($id);
		$file = $this->file($filedata["hash"]);

		if ($this->config->item("upload_max_age") == 0) {
			return -1;
		}

		if (filesize($file) > $this->config->item("small_upload_size")) {
			return $filedata["date"] + $this->config->item("upload_max_age");
		} else {
			return -1;
		}
	}

	public function get_timeout_string($id)
	{
		$timeout = $this->get_timeout($id);

		if ($timeout >= 0) {
			return date("r", $timeout);
		} else {
			return "unknown";
		}
	}

	private function unused_file($hash)
	{
		$query = $this->db->select('id')
			->from('files')
			->where('hash', $hash)
			->limit(1)
			->get();

		if ($query->num_rows() == 0) {
			return true;
		} else {
			return false;
		}
	}

	public function delete_id($id)
	{
		$filedata = $this->get_filedata($id);

		// Delete the file and all multipastes using it
		// Note that this does not delete all relations in multipaste_file_map
		// which is actually done by a SQL contraint.
		// TODO: make it work properly without the constraint
		$map = $this->db->select('url_id')
			->distinct()
			->from('multipaste_file_map')
			->join("multipaste", "multipaste.multipaste_id = multipaste_file_map.multipaste_id")
			->where('file_url_id', $id)
			->get()->result_array();

		$this->db->where('id', $id)
			->delete('files');

		foreach ($map as $entry) {
			assert(!empty($entry['url_id']));
			$this->mmultipaste->delete_id($entry["url_id"]);
		}

		if ($this->id_exists($id))  {
			return false;
		}

		if ($filedata !== false) {
			assert(isset($filedata["hash"]));
			if ($this->unused_file($filedata['hash'])) {
				if (file_exists($this->file($filedata['hash']))) {
					unlink($this->file($filedata['hash']));
				}
				$dir = $this->folder($filedata['hash']);
				if (file_exists($dir)) {
					if (count(scandir($dir)) == 2) {
						rmdir($dir);
					}
				}
			}
		}
		return true;
	}

	public function delete_hash($hash)
	{
		$ids = $this->db->select('id')
			->from('files')
			->where('hash', $hash)
			->get()->result_array();

		foreach ($ids as $entry) {
			$this->delete_id($entry["id"]);
		}
	}

	public function get_owner($id)
	{
		return $this->db->select('user')
			->from('files')
			->where('id', $id)
			->get()->row_array()
			['user'];
	}

	public function get_lexers() {
		return cache_function('lexers', 1800, function() {
			$lexers = array();
			$last_desc = "";
			exec("python ".escapeshellarg(FCPATH."scripts/get_lexer_list.py"), $output);

			foreach ($output as $line) {
				list($name, $desc) = explode("|", $line);
				if ($desc == $last_desc) {
					continue;
				}
				$last_desc = $desc;
				$lexers[$name] = $desc;
			}
			$lexers["text"] = "Plain text";
			return $lexers;
		});
	}

	public function should_highlight($type)
	{
		if ($this->mime2lexer($type)) return true;

		return false;
	}

	// Allow certain types to be highlight without doing it automatically
	public function can_highlight($type)
	{
		$typearray = array(
			'image/svg+xml',
		);
		if (in_array($type, $typearray)) return true;

		if ($this->mime2lexer($type)) return true;

		return false;
	}

	// Return the lexer that should be used for highlighting
	public function autodetect_lexer($type, $filename)
	{
		if (!$this->can_highlight($type)) {
			return false;
		}

		$lexer = $this->mime2lexer($type);

		// filename lexers overwrite mime type mappings
		$filename_lexer = $this->filename2lexer($filename);
		if ($filename_lexer) {
			return $filename_lexer;
		}

		return $lexer;
	}

	// Map MIME types to lexers needed for highlighting
	private function mime2lexer($type)
	{
		$typearray = array(
		'application/javascript' => 'javascript',
		'application/mbox' => 'text',
		'application/postscript' => 'postscript',
		'application/smil' => 'ocaml',
		'application/x-applix-spreadsheet' => 'actionscript',
		'application/x-awk' => 'awk',
		'application/x-desktop' => 'text',
		'application/x-fluid' => 'text',
		'application/x-genesis-rom' => 'text',
		'application/x-java' => 'java',
		'application/x-m4' => 'text',
		'application/xml-dtd' => "xml",
		'application/xml' => 'xml',
		'application/x-perl' => 'perl',
		'application/x-php' => 'php',
		'application/x-ruby' => 'ruby',
		'application/x-shellscript' => 'bash',
		'application/xslt+xml' => "xml",
		'application/x-x509-ca-cert' => 'text',
		'message/rfc822' => 'text',
		'text/css' => 'css',
		'text/html' => 'xml',
		'text/plain-ascii' => 'ascii',
		'text/plain' => 'text',
		'text/troff' => 'groff',
		'text/x-asm' => 'nasm',
		'text/x-awk' => 'awk',
		'text/x-c' => 'c',
		'text/x-c++' => 'cpp',
		'text/x-c++hdr' => 'c',
		'text/x-chdr' => 'c',
		'text/x-csrc' => 'c',
		'text/x-c++src' => 'cpp',
		'text/x-diff' => 'diff',
		'text/x-gawk' => 'awk',
		'text/x-haskell' => 'haskell',
		'text/x-java' => 'java',
		'text/x-lisp' => 'cl',
		'text/x-literate-haskell' => 'haskell',
		'text/x-lua' => 'lua',
		'text/x-makefile' => 'make',
		'text/x-ocaml' => 'ocaml',
		'text/x-patch' => 'diff',
		'text/x-perl' => 'perl',
		'text/x-php' => 'php',
		'text/x-python' => 'python',
		'text/x-ruby' => 'ruby',
		'text/x-scheme' => 'scheme',
		'text/x-shellscript' => 'bash',
		'text/x-subviewer' => 'bash',
		'text/x-tcl' => 'tcl',
		'text/x-tex' => 'tex',
		);
		if (array_key_exists($type, $typearray)) return $typearray[$type];

		if (strpos($type, 'text/') === 0) return 'text';

		# default
		return false;
	}

	// Map special filenames to lexers
	private function filename2lexer($name)
	{
		$namearray = array(
			'PKGBUILD' => 'bash',
			'.vimrc' => 'vim'
		);
		if (array_key_exists($name, $namearray)) return $namearray[$name];


		if (strpos($name, ".") !== false) {
			$extension = substr($name, strrpos($name, ".") + 1);

			$extensionarray = array(
				'awk' => 'awk',
				'c' => 'c',
				'coffee' => 'coffee-script',
				'cpp' => 'cpp',
				'diff' => 'diff',
				'h' => 'c',
				'hs' => 'haskell',
				'html' => 'xml',
				'java' => 'java',
				'js' => 'js',
				'lua' => 'lua',
				'mli' => 'ocaml',
				'mll' => 'ocaml',
				'ml' => 'ocaml',
				'mly' => 'ocaml',
				'patch' => 'diff',
				'php' => 'php',
				'pl' => 'perl',
				'pp' => 'puppet',
				'py' => 'python',
				'rb' => 'ruby',
				's' => 'asm',
				'sh' => 'bash',
				'tcl' => 'tcl',
				'tex' => 'tex',
			);
			if (array_key_exists($extension, $extensionarray)) return $extensionarray[$extension];
		}

		return false;
	}

	// Handle lexer aliases
	public function resolve_lexer_alias($alias)
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
