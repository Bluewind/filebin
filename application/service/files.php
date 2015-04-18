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

		// TODO: thumbnail urls where available
		$fields = array("id", "filename", "mimetype", "date", "hash", "filesize");
		$query = $CI->db->select(implode(',', $fields))
			->from('files')
			->where('user', $user)
			->get()->result_array();
		foreach ($query as $key => $item) {
			$items[$item["id"]] = $item;
		}

		$total_size = $CI->db->query("
			SELECT coalesce(sum(filesize), 0) sum
			FROM (
				SELECT DISTINCT hash, filesize
				FROM `".$CI->db->dbprefix."files`
				WHERE user = ?
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

		$query = $CI->db->get_where("multipaste", array("user_id" => $user))->result_array();
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
			->get()->result_array();

		foreach ($multipaste_items_query as $item) {
			$multipaste_info[$item["url_id"]]["items"][$item["id"]] = array("id" => $item["id"]);
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

	static public function add_file_data($id, $content, $filename)
	{
		self::add_file_callback($id, $filename, array(
			"hash" => function() use ($content) {
				return md5($content);
			},
			"data_writer" => function($dest) use ($content) {
				file_put_contents($dest, $content);
			}
		));
	}

	static public function add_uploaded_file($id, $file, $filename)
	{
		self::add_file_callback($id, $filename, array(
			"hash" => function() use ($file) {
				return md5_file($file);
			},
			"data_writer" => function($dest) use ($file) {
				move_uploaded_file($file, $dest);
			}
		));
	}

	// TODO: an interface would be nice here, but php doesn't support anonymous
	// objects so let's use an array for now
	static private function add_file_callback($id, $filename, $callbacks)
	{
		assert(isset($callbacks["hash"]));
		assert(isset($callbacks["data_writer"]));

		$CI =& get_instance();
		$hash = $callbacks["hash"]();

		$dir = $CI->mfile->folder($hash);
		file_exists($dir) || mkdir ($dir);
		$new_path = $CI->mfile->file($hash);

		$dest = new \service\storage($new_path);
		$tmpfile = $dest->begin();
		$callbacks["data_writer"]($tmpfile);
		$dest->commit();

		$CI->mfile->add_file($hash, $id, $filename);
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
		assert(isset($filedata["hash"]));
		assert(isset($filedata["id"]));
		assert(isset($filedata["user"]));
		assert(isset($filedata["date"]));
		assert(isset($config["upload_max_age"]));
		assert(isset($config["sess_expiration"]));
		assert(isset($config["small_upload_size"]));

		$file = $model->file($filedata['hash']);

		if (!$model->file_exists($file)) {
			$model->delete_hash($filedata["hash"]);
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
				$model->delete_hash($filedata["hash"]);
			} else {
				$model->delete_id($filedata["id"]);
			}
			return false;
		}

		return true;
	}
}
