<?php
/*
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace exceptions;

class FileUploadVerifyException extends VerifyException {
	public function __toString()
	{
		$ret = $this->getMessage()."\n";
		$data = $this->get_data();
		$errors = array();

		foreach ($data as $error) {
			$errors[] = sprintf("%s: %s", $error["filename"], $error["message"]);
		}

		$ret .= implode("\n", $errors);
		return $ret;
	}
}
