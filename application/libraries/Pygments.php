<?php
/*
 * Copyright 2015 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace libraries;

class Pygments {
	private $file;
	private $mimetype;
	private $filename;

	public function __construct($file, $mimetype, $filename) {
		$this->file = $file;
		$this->mimetype = $mimetype;
		$this->filename = $filename;
	}

	private static function get_pygments_info() {
		return cache_function_full('pygments_info-v2', 1800, function() {
			$r = (new \libraries\ProcRunner(array(FCPATH."scripts/get_lexer_list.py")))->execSafe();

			$ret = json_decode($r["stdout"], true);
			if ($ret === NULL) {
				throw new \exceptions\ApiException('pygments/json-failed', "Failed to decode JSON", $r);
			}

			return $ret;
		});
	}

	public static function get_lexers() {
		return cache_function('lexers-v2', 1800, function() {
			$last_desc = "";

			foreach (self::get_pygments_info() as $lexer) {
				$desc = $lexer['fullname'];
				$name = $lexer['names'][0];
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

	// Allow certain types to be highlight without doing it automatically
	public function should_highlight()
	{
		$typearray = array(
			'image/svg+xml',
		);
		if (in_array($this->mimetype, $typearray)) return false;

		if ($this->mime2lexer($this->mimetype)) return true;

		return false;
	}

	public function can_highlight()
	{
		if ($this->mime2lexer($this->mimetype)) return true;

		return false;
	}

	// Return the lexer that should be used for highlighting
	public function autodetect_lexer()
	{
		if (!$this->should_highlight()) {
			return false;
		}

		$lexer = $this->mime2lexer($this->mimetype);

		// filename lexers overwrite mime type mappings
		$filename_lexer = $this->filename2lexer();
		if ($filename_lexer) {
			return $filename_lexer;
		}

		return $lexer;
	}

	// Map MIME types to lexers needed for highlighting
	private function mime2lexer()
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
		if (array_key_exists($this->mimetype, $typearray)) return $typearray[$this->mimetype];

		// fall back to pygments own list if not found in our list
		foreach (self::get_pygments_info() as $lexer) {
			if (isset($lexer['mimetypes'][$this->mimetype])) {
				return $lexer['names'][0];
			}
		}

		if (strpos($this->mimetype, 'text/') === 0) return 'text';

		# default
		return false;
	}

	// Map special filenames to lexers
	private function filename2lexer()
	{
		$namearray = array(
			'PKGBUILD' => 'bash',
			'.vimrc' => 'vim'
		);
		if (array_key_exists($this->filename, $namearray)) return $namearray[$this->filename];


		if (strpos($this->filename, ".") !== false) {
			$extension = substr($this->filename, strrpos($this->filename, ".") + 1);

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
				'lhs' => 'lhs',
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
