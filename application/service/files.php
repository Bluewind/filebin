<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace service;

class files {

	static public function history($user)
	{
		$CI =& get_instance();
		$multipaste_items_grouped = array();
		$multipaste_items = array();

		$fields = array("id", "filename", "mimetype", "date", "hash", "filesize");

		$items = $CI->db->select(implode(',', $fields))
			->from('files')
			->where('user', $user)
			->get()->result_array();

		$multipaste_items_query = $CI->db
			->select("m.url_id, f.filename, f.id, f.filesize, f.date, f.hash, f.mimetype")
			->from("multipaste m")
			->join("multipaste_file_map mfm", "m.multipaste_id = mfm.multipaste_id")
			->join("files f", "f.id = mfm.file_url_id")
			->where("m.user_id", $user)
			->get()->result_array();

		foreach ($multipaste_items_query as $item) {
			$key = $item["url_id"];
			unset($item["url_id"]);
			$multipaste_items_grouped[$key][] = $item;
		}

		foreach ($multipaste_items_grouped as $key => $items) {
			$multipaste_info = $CI->db->get_where("multipaste", array("url_id" => $key))->row_array();
			$multipaste_info["items"] = $items;
			$multipaste_items[] = $multipaste_info;
		}

		$total_size = $CI->db->query("
			SELECT sum(filesize) sum
			FROM (
				SELECT DISTINCT hash, filesize
				FROM files
				WHERE user = ?
			) sub
			", array($user))->row_array();

		$ret["items"] = $items;
		$ret["multipaste_items"] = $multipaste_items;
		$ret["total_size"] = $total_size["sum"];

		return $ret;
	}

	static public function add_file($id, $file, $filename)
	{
		$CI =& get_instance();
		$hash = md5_file($file);

		$dir = $CI->mfile->folder($hash);
		file_exists($dir) || mkdir ($dir);
		$new_path = $CI->mfile->file($hash);

		// TODO: make this operation atomic (move to temp name, then to final)
		// the source can be a different file system so this might do a copy
		move_uploaded_file($file, $new_path);
		$CI->mfile->add_file($hash, $id, $filename);
	}

	static public function verify_uploaded_files($files)
	{
		$CI =& get_instance();
		$errors = array();

		foreach ($files as $key => $file) {
			$error_message = "";

			// getNormalizedFILES() removes any file with error == 4
			if ($file['error'] !== UPLOAD_ERR_OK) {
				// ERR_OK only for completeness, condition above ignores it
				$error_msgs = array(
					UPLOAD_ERR_OK => "There is no error, the file uploaded with success",
					UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive in php.ini",
					UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
					UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
					UPLOAD_ERR_NO_FILE => "No file was uploaded",
					UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
					UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
					UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload",
				);

				$error_message = "Unknown error.";

				if (isset($error_msgs[$file['error']])) {
					$error_message = $error_msgs[$file['error']];
				} else {
					$error_message = "Unknown error code: ".$file['error'].". Please report a bug.";
				}

			}

			$filesize = filesize($file['tmp_name']);
			if ($filesize > $CI->config->item('upload_max_size')) {
				$error_message = "File too big";
			}

			if ($error_message != "") {
				$errors[] = array(
					"filename" => $file["name"],
					"formfield" => $file["formfield"],
					"message" => $error_message,
				);
			}
		}

		return $errors;
	}
}
