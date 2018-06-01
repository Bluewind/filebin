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
		$items = array();

		$fields = array("files.id", "filename", "mimetype", "files.date", "hash", "filesize");
		$query = $CI->db->select(implode(',', $fields))
			->from('files')
			->join('file_storage', 'file_storage.id = files.file_storage_id')
			->where('user', $user)
			->get()->result_array();
		foreach ($query as $key => $item) {
			if (\libraries\Image::type_supported($item["mimetype"])) {
				$item['thumbnail'] = site_url("file/thumbnail/".$item['id']);
			}
			$items[$item["id"]] = $item;
		}

		$total_size = $CI->db->query("
			SELECT coalesce(sum(sub.filesize), 0) sum
			FROM (
				SELECT DISTINCT fs.id, filesize
				FROM ".$CI->db->dbprefix."file_storage fs
				JOIN ".$CI->db->dbprefix."files f ON fs.id = f.file_storage_id
				WHERE f.user = ?

			) sub
			", array($user))->row_array();

		$ret["items"] = $items;
		$ret["multipaste_items"] = self::get_multipaste_history($user);
		$ret["total_size"] = $total_size["sum"];

		return $ret;
	}

	static private function get_multipaste_history($user)
	{
		$CI =& get_instance();
		$multipaste_items_grouped = array();
		$multipaste_items = array();

		$query = $CI->db
			->select('m.url_id, m.date')
			->from("multipaste m")
			->where("user_id", $user)
			->get()->result_array();
		$multipaste_info = array();
		foreach ($query as $item) {
			$multipaste_info[$item["url_id"]] = $item;
		}

		$multipaste_items_query = $CI->db
			->select("m.url_id, f.id")
			->from("multipaste m")
			->join("multipaste_file_map mfm", "m.multipaste_id = mfm.multipaste_id")
			->join("files f", "f.id = mfm.file_url_id")
			->where("m.user_id", $user)
			->order_by("mfm.sort_order")
			->get()->result_array();

		$counter = 0;

		foreach ($multipaste_items_query as $item) {
			$multipaste_info[$item["url_id"]]["items"][$item["id"]] = array(
				"id" => $item["id"],
				// normalize sort_order value so we don't leak any information
				"sort_order" => $counter++,
			);
		}

		// No idea why, but this can/could happen so be more forgiving and clean up
		foreach ($multipaste_info as $key => $m) {
			if (!isset($m["items"])) {
				$CI->mmultipaste->delete_id($key);
				unset($multipaste_info[$key]);
			}
		}

		return $multipaste_info;
	}

	static public function add_file_data($userid, $id, $content, $filename)
	{
		$f = new \libraries\Tempfile();
		$file = $f->get_file();
		file_put_contents($file, $content);
		self::add_file_callback($userid, $id, $file, $filename);
	}

	/**
	 * Ellipsize text to be at max $max_lines lines long. If the last line is
	 * not complete (strlen($text) < $filesize), drop it so that every line of
	 * the returned text is complete. If there is only one line, return that
	 * line as is and add the ellipses at the end.
	 *
	 * @param text Text to add ellipses to
	 * @param max_lines Number of lines the returned text should contain
	 * @param filesize size of the original file where the text comes from
	 * @return ellipsized text
	 */
	static public function ellipsize($text, $max_lines, $filesize)
	{
		$lines = explode("\n", $text);
		$orig_len = strlen($text);
		$orig_linecount = count($lines);

		if ($orig_linecount > 1) {
			if ($orig_len < $filesize) {
				// ensure we have a full line at the end
				$lines = array_slice($lines, 0, -1);
			}

			if (count($lines) > $max_lines) {
				$lines = array_slice($lines, 0, $max_lines);
			}

			if (count($lines) != $orig_linecount) {
				// only add elipses when we drop at least one line
				$lines[] = "...";
			}
		} elseif ($orig_len < $filesize) {
			$lines[count($lines) - 1] .= " ...";
		}

		return implode("\n", $lines);
	}

	static public function add_uploaded_file($userid, $id, $file, $filename)
	{
		self::add_file_callback($userid, $id, $file, $filename);
	}

	static private function add_file_callback($userid, $id, $new_file, $filename)
	{
		$CI =& get_instance();
		$hash = md5_file($new_file);
		$storage_id = null;

		$query = $CI->db->select('id, hash')
			->from('file_storage')
			->where('hash', $hash)
			->get()->result_array();

		foreach($query as $row) {
			$data_id = implode("-", array($row['hash'], $row['id']));
			$old_file = $CI->mfile->file($data_id);

			if (files_are_equal($old_file, $new_file)) {
				$storage_id = $row["id"];
				break;
			}
		}

		$new_storage_id_created = false;
		if ($storage_id === null) {
			$filesize = filesize($new_file);
			$mimetype = mimetype($new_file);

			$CI->db->insert("file_storage", array(
				"filesize" => $filesize,
				"mimetype" => $mimetype,
				"hash" => $hash,
				"date" => time(),
			));
			$storage_id = $CI->db->insert_id();
			$new_storage_id_created = true;
			assert(!file_exists($CI->mfile->file($hash."-".$storage_id)));
		}
		$data_id = $hash."-".$storage_id;

		$dir = $CI->mfile->folder($data_id);
		file_exists($dir) || mkdir ($dir);
		$new_path = $CI->mfile->file($data_id);

		// Update mtime for cronjob
		touch($new_path);

		// touch may create a new file if the cronjob cleaned up in between the db check and here.
		// In that case the file will be empty so move in the data
		if ($new_storage_id_created || filesize($new_path) === 0) {
			$dest = new \service\storage($new_path);
			$tmpfile = $dest->begin();

			// $new_file may reside on a different file system so this call
			// could perform a copy operation internally. $dest->commit() will
			// ensure that it performs an atomic overwrite (rename).
			rename($new_file, $tmpfile);
			$dest->commit();
		}

		$CI->mfile->add_file($userid, $id, $filename, $storage_id);
	}

	static public function verify_uploaded_files($files)
	{
		$CI =& get_instance();
		$errors = array();

		if (empty($files)) {
			throw new \exceptions\UserInputException("file/no-file", "No file was uploaded or unknown error occured.");
		}

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
				$errors[$file["formfield"]] = array(
					"filename" => $file["name"],
					"formfield" => $file["formfield"],
					"message" => $error_message,
				);
				throw new \exceptions\FileUploadVerifyException("file/upload-verify", "Failed to verify uploaded file(s)", $errors);
			}
		}
	}

	// TODO: streamline this interface to be somewhat atomic in regards to
	// wrong owner/unknown ids (verify first and throw exception)
	static public function delete($ids)
	{
		$CI =& get_instance();

		$userid = $CI->muser->get_userid();
		$errors = array();
		$deleted = array();
		$deleted_count = 0;
		$total_count = 0;

		if (!$ids || !is_array($ids)) {
			throw new \exceptions\UserInputException("file/delete/no-ids", "No IDs specified");
		}

		foreach ($ids as $id) {
			$total_count++;
			$nextID = false;

			foreach (array($CI->mfile, $CI->mmultipaste) as $model) {
				if ($model->id_exists($id)) {
					if ($model->get_owner($id) !== $userid) {
						$errors[$id] = array(
							"id" => $id,
							"reason" => "wrong owner",
						);
						$nextID = true;
						continue;
					}
					if ($model->delete_id($id)) {
						$deleted[$id] = array(
							"id" => $id,
						);
						$deleted_count++;
						$nextID = true;
					} else {
						$errors[$id] = array(
							"id" => $id,
							"reason" => "unknown error",
						);
					}
				}
			}

			if ($nextID) {
				continue;
			}

			$errors[$id] = array(
				"id" => $id,
				"reason" => "doesn't exist",
			);
		}

		return array(
			"errors" => $errors,
			"deleted" => $deleted,
			"total_count" => $total_count,
			"deleted_count" => $deleted_count,
		);
	}

	static public function create_multipaste($ids, $userid, $limits)
	{
		$CI =& get_instance();

		if (!$ids || !is_array($ids)) {
			throw new \exceptions\UserInputException("file/create_multipaste/no-ids", "No IDs specified");
		}

		if (count(array_unique($ids)) != count($ids)) {
			throw new \exceptions\UserInputException("file/create_multipaste/duplicate-id", "Duplicate IDs are not supported");
		}

		$errors = array();

		foreach ($ids as $id) {
			if (!$CI->mfile->id_exists($id)) {
				$errors[$id] = array(
					"id" => $id,
					"reason" => "doesn't exist",
				);
				continue;
			}

			$filedata = $CI->mfile->get_filedata($id);
			if ($filedata["user"] != $userid) {
				$errors[$id] = array(
					"id" => $id,
					"reason" => "not owned by you",
				);
			}
		}

		if (!empty($errors)) {
			throw new \exceptions\VerifyException("file/create_multipaste/verify-failed", "Failed to verify ID(s)", $errors);
		}

		$url_id = $CI->mmultipaste->new_id($limits[0], $limits[1]);

		$multipaste_id = $CI->mmultipaste->get_multipaste_id($url_id);
		assert($multipaste_id !== false);

		foreach ($ids as $id) {
			$CI->db->insert("multipaste_file_map", array(
				"file_url_id" => $id,
				"multipaste_id" => $multipaste_id,
			));
		}

		return array(
			"url_id" => $url_id,
			"url" => site_url($url_id)."/",
		);
	}

	static public function valid_id(array $filedata, array $config, $model, $current_date)
	{
		assert(isset($filedata["data_id"]));
		assert(isset($filedata["id"]));
		assert(isset($filedata["user"]));
		assert(isset($filedata["date"]));
		assert(isset($config["upload_max_age"]));
		assert(isset($config["sess_expiration"]));
		assert(isset($config["small_upload_size"]));

		$file = $model->file($filedata['data_id']);

		if (!$model->file_exists($file)) {
			$model->delete_data_id($filedata["data_id"]);
			return false;
		}

		if ($filedata["user"] == 0) {
			if ($filedata["date"] < $current_date - $config["sess_expiration"]) {
				$model->delete_id($filedata["id"]);
				return false;
			}
		}

		// 0 age disables age checks
		if ($config['upload_max_age'] == 0) return true;

		// small files don't expire
		if ($model->filesize($file) <= $config["small_upload_size"]) {
			return true;
		}

		// files older than this should be removed
		$remove_before = $current_date - $config["upload_max_age"];

		if ($filedata["date"] < $remove_before) {
			// if the file has been uploaded multiple times the mtime is the time
			// of the last upload
			$mtime = $model->filemtime($file);
			if ($mtime < $remove_before) {
				$model->delete_data_id($filedata["data_id"]);
			} else {
				$model->delete_id($filedata["id"]);
			}
			return false;
		}

		return true;
	}

	static public function tooltip(array $filedata)
	{
		$filesize = format_bytes($filedata["filesize"]);
		$file = get_instance()->mfile->file($filedata["data_id"]);
		$upload_date = date("r", $filedata["date"]);

		$height = 0;
		$width = 0;
		try {
			list($width, $height) = getimagesize($file);
		} catch (\ErrorException $e) {
			// likely unsupported filetype
		}

		$tooltip  = "${filedata["id"]} - $filesize<br>";
		$tooltip .= "$upload_date<br>";


		if ($height > 0 && $width > 0) {
			$tooltip .= "${width}x${height} - ${filedata["mimetype"]}<br>";
		} else {
			$tooltip .= "${filedata["mimetype"]}<br>";
		}

		return $tooltip;
	}

	static public function clean_multipaste_tarballs()
	{
		$CI =& get_instance();

		$tarball_dir = $CI->config->item("upload_path")."/special/multipaste-tarballs";
		if (is_dir($tarball_dir)) {
			$tarball_cache_time = $CI->config->item("tarball_cache_time");
			$it = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($tarball_dir), \RecursiveIteratorIterator::SELF_FIRST);

			foreach ($it as $file) {
				if ($file->isFile()) {
					if ($file->getMTime() < time() - $tarball_cache_time) {
						$lock = fopen($file, "r+");
						flock($lock, LOCK_EX);
						unlink($file);
						flock($lock, LOCK_UN);
					}
				}
			}
		}
	}

	static public function remove_files_missing_in_db()
	{
		$CI =& get_instance();

		$upload_path = $CI->config->item("upload_path");
		$outer_dh = opendir($upload_path);

		while (($dir = readdir($outer_dh)) !== false) {
			if (!is_dir($upload_path."/".$dir) || $dir == ".." || $dir == "." || $dir == "special") {
				continue;
			}

			$dh = opendir($upload_path."/".$dir);

			$empty = true;

			while (($file = readdir($dh)) !== false) {
				if ($file == ".." || $file == ".") {
					continue;
				}

				try {
					list($hash, $storage_id) = explode("-", $file);
				} catch (\ErrorException $e) {
					unlink($upload_path."/".$dir."/".$file);
					continue;
				}

				$query = $CI->db->select('hash, id')
					->from('file_storage')
					->where('hash', $hash)
					->where('id', $storage_id)
					->limit(1)
					->get()->row_array();

				if (empty($query)) {
					$CI->mfile->delete_data_id($file);
				} else {
					$empty = false;
				}
			}

			closedir($dh);

			if ($empty && file_exists($upload_path."/".$dir)) {
				rmdir($upload_path."/".$dir);
			}
		}
		closedir($outer_dh);
	}

	static public function remove_files_missing_on_disk()
	{
		$CI =& get_instance();

		$chunk = 500;
		$total = $CI->db->count_all("file_storage");

		for ($limit = 0; $limit < $total; $limit += $chunk) {
			$query = $CI->db->select('hash, id')
				->from('file_storage')
				->limit($chunk, $limit)
				->get()->result_array();

			foreach ($query as $key => $item) {
				$data_id = $item["hash"].'-'.$item['id'];
				$file = $CI->mfile->file($data_id);

				if (!$CI->mfile->file_exists($file)) {
					$CI->mfile->delete_data_id($data_id);
				}
			}
		}
	}

}
