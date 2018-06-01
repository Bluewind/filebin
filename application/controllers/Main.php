<?php
/*
 * Copyright 2009-2013 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Main extends MY_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('mfile');
		$this->load->model('mmultipaste');
	}

	function index()
	{
		if (is_cli()) {
			output_cli_usage();
			exit;
		}

		// Try to guess what the user would like to do.
		$id = $this->uri->segment(1);
		if (strpos($id, "m-") === 0 && $this->mmultipaste->id_exists($id)) {
			$this->_download();
		} elseif ($id != "file" && $this->mfile->id_exists($id)) {
			$this->_download();
		} elseif ($id && $id != "file") {
			$this->_non_existent();
		} else {
			$this->upload_form();
		}
	}

	/**
	 * Generate a page title of the format "Multipaste - $filename, $filename, … (N more)".
	 * This mainly helps in IRC channels to quickly determine what is in a multipaste.
	 *
	 * @param files array of filedata
	 * @return title to be used
	 */
	private function _multipaste_page_title(array $files)
	{
		$filecount = count($files);
		$title = "Multipaste ($filecount files) - ";
		$titlenames = array();
		$len = strlen($title);
		$delimiter = ', ';
		$maxlen = 100;

		foreach ($files as $file) {
			if ($len > $maxlen) break;

			$filename = $file['filename'];
			$titlenames[] = htmlspecialchars($filename);
			$len += strlen($filename) + strlen($delimiter);
		}

		$title .= implode($delimiter, $titlenames);

		$leftover_count = $filecount - count($titlenames);

		if ($leftover_count > 0) {
			$title .= $delimiter.'… ('.$leftover_count.' more)';
		}

		return $title;
	}

	function _download()
	{
		session_write_close();
		$id = $this->uri->segment(1);
		$lexer = urldecode($this->uri->segment(2));

		$is_multipaste = false;
		if ($this->mmultipaste->id_exists($id)) {
			$is_multipaste = true;

			if(!$this->mmultipaste->valid_id($id)) {
				$this->mmultipaste->delete_id($id);
				return $this->_non_existent();
			}
			$files = $this->mmultipaste->get_files($id);
			$this->data["title"] = $this->_multipaste_page_title($files);
		} elseif ($this->mfile->id_exists($id)) {
			if (!$this->mfile->valid_id($id)) {
				return $this->_non_existent();
			}

			$files = array($this->mfile->get_filedata($id));
			$this->data["title"] = htmlspecialchars($files[0]["filename"]);
		} else {
			assert(0);
		}

		assert($files !== false);
		assert(is_array($files));
		assert(count($files) >= 1);

		// don't allow unowned files to be downloaded
		foreach ($files as $filedata) {
			if ($filedata["user"] == 0) {
				return $this->_non_existent();
			}
		}

		$etag = "";
		foreach ($files as $filedata) {
			$etag = sha1($etag.$filedata["data_id"]);
		}

		// handle some common "lexers" here
		switch ($lexer) {
		case "":
			break;

		case "qr":
			handle_etag($etag);
			header("Content-disposition: inline; filename=\"".$id."_qr.png\"\n");
			header("Content-Type: image/png\n");
			$qr = new \Endroid\QrCode\QrCode();
			$qr->setText(site_url($id).'/')
			   ->setSize(350)
			   ->setErrorCorrection('low')
			   ->render();
			exit();

		case "info":
			return $this->_display_info($id);

		case "tar":
			if ($is_multipaste) {
				return $this->_tarball($id);
			}

		case "pls":
			if ($is_multipaste) {
				return $this->_generate_playlist($id);
			}

		default:
			if ($is_multipaste) {
				throw new \exceptions\UserInputException("file/download/invalid-action", "Invalid action \"".htmlspecialchars($lexer)."\"");
			}
			break;
		}

		$this->load->driver("ddownload");

		// user wants the plain file
		if ($lexer == 'plain') {
			assert(count($files) == 1);
			handle_etag($etag);

			$filedata = $files[0];
			$filepath = $this->mfile->file($filedata["data_id"]);
			$this->ddownload->serveFile($filepath, $filedata["filename"], "text/plain");
			exit();
		}

		$output_cache = new \libraries\Output_cache();

		foreach ($files as $key => $filedata) {
			$file = $this->mfile->file($filedata['data_id']);
			$pygments = new \libraries\Pygments($file, $filedata["mimetype"], $filedata["filename"]);

			// autodetect the lexer for highlighting if the URL contains a / after the ID (/ID/)
			// /ID/lexer disables autodetection
			$autodetect_lexer = !$lexer && preg_match('/^[^?]*\/(\?.*)?$/', $_SERVER['REQUEST_URI']);
			$autodetect_lexer = $is_multipaste ? true : $autodetect_lexer;
			if ($autodetect_lexer) {
				$lexer = $pygments->autodetect_lexer();
			}

			// resolve aliases
			// this is mainly used for compatibility
			$lexer = $pygments->resolve_lexer_alias($lexer);

			// if there is no mimetype mapping we can't highlight it
			$can_highlight = $pygments->can_highlight();

			$filesize_too_big = filesize($file) > $this->config->item('upload_max_text_size');

			if ($lexer == "asciinema") {
				$output_cache->add(array("filedata" => $filedata), "file/fragments/asciinema-player");
				continue;
			}

			if (!$can_highlight || $filesize_too_big || !$lexer) {
				if (!$is_multipaste) {
					// prevent javascript from being executed and forbid frames
					// this should allow us to serve user submitted HTML content without huge security risks
					foreach (array("X-WebKit-CSP", "X-Content-Security-Policy", "Content-Security-Policy") as $header_name) {
						header("$header_name: default-src 'none'; img-src *; media-src *; font-src *; style-src 'unsafe-inline' *; script-src 'none'; object-src *; frame-src 'none'; ");
					}
					handle_etag($etag);
					$this->ddownload->serveFile($file, $filedata["filename"], $filedata["mimetype"]);
					exit();
				} else {
					$mimetype = $filedata["mimetype"];
					$base = explode("/", $filedata["mimetype"])[0];

					if (\libraries\Image::type_supported($mimetype)) {
						$filedata["tooltip"] = \service\files::tooltip($filedata);
						$filedata["orientation"] = libraries\Image::get_exif_orientation($file);
						$output_cache->add_merge(
							array("items" => array($filedata)),
							'file/fragments/thumbnail'
						);
					} else if ($base == "audio") {
						$output_cache->add(array("filedata" => $filedata), "file/fragments/audio-player");
					} else if ($base == "video") {
						$output_cache->add(array("filedata" => $filedata), "file/fragments/video-player");
					} else {
						$output_cache->add_merge(
							array("items" => array($filedata)),
							'file/fragments/uploads_table'
						);
					}
					continue;
				}
			}

			$output_cache->add_function(function() use ($output_cache, $filedata, $lexer, $is_multipaste) {
				$renderer = new \service\renderer($output_cache, $this->mfile, $this->data);
				$renderer->highlight_file($filedata, $lexer, $is_multipaste);
			});
		}

		// TODO: move lexers json to dedicated URL
		$this->data['lexers'] = \libraries\Pygments::get_lexers();

		// Output everything
		// Don't use the output class/append_output because it does too
		// much magic ({elapsed_time} and {memory_usage}).
		// Direct echo puts us on the safe side.
		echo $this->load->view('file/html_header', $this->data, true);
		$output_cache->render();
		echo $this->load->view('file/html_footer', $this->data, true);
	}

	private function _display_info($id)
	{
		if ($this->mmultipaste->id_exists($id)) {
			$files = $this->mmultipaste->get_files($id);

			$this->data["title"] .= " - Info $id";

			$multipaste = $this->mmultipaste->get_multipaste($id);
			$total_size = 0;
			$timeout = -1;
			foreach($files as $filedata) {
				$total_size += $filedata["filesize"];
				$file_timeout = $this->mfile->get_timeout($filedata["id"]);
				if ($timeout == -1 || ($timeout > $file_timeout && $file_timeout >= 0)) {
					$timeout = $file_timeout;
				}
			}

			$data = array_merge($this->data, array(
				'timeout_string' => $timeout >= 0 ? date("r", $timeout) : "Never",
				'upload_date' => $multipaste["date"],
				'id' => $id,
				'size' => $total_size,
				'file_count' => count($files),
			));

			$this->load->view('header', $this->data);
			$this->load->view('file/multipaste_info', $data);
			$this->load->view('footer', $this->data);
			return;
		} elseif ($this->mfile->id_exists($id)) {
			$this->data["title"] .= " - Info $id";
			$this->data["filedata"] = $this->mfile->get_filedata($id);
			$this->data["id"] = $id;
			$this->data['timeout'] = $this->mfile->get_timeout_string($id);

			$this->load->view('header', $this->data);
			$this->load->view('file/file_info', $this->data);
			$this->load->view('footer', $this->data);
		}
	}

	private function _tarball($id)
	{
		if ($this->mmultipaste->id_exists($id)) {
			$seen = array();
			$path = $this->mmultipaste->get_tarball_path($id);
			$archive = new \service\storage($path);

			if (!$archive->exists()) {
				$files = $this->mmultipaste->get_files($id);

				$total_size = 0;
				foreach ($files as $filedata) {
					$total_size += $filedata["filesize"];
				}

				if ($total_size > $this->config->item("tarball_max_size")) {
					throw new \exceptions\PublicApiException("file/tarball/tarball-filesize-limit", "Tarball too large, refusing to create.");
				}

				$tmpfile = $archive->begin();
				// create empty tar archive so PharData has something to open
				file_put_contents($tmpfile, str_repeat("\0", 1024*10));
				$a = new PharData($tmpfile);

				foreach ($files as $filedata) {
					$filename = $filedata["filename"];
					if (isset($seen[$filename]) && $seen[$filename]) {
						$filename = $filedata["id"]."-".$filedata["filename"];
					}
					assert(!isset($seen[$filename]));
					$a->addFile($this->mfile->file($filedata["data_id"]), $filename);
					$seen[$filename] = true;
				}
				$archive->gzip_compress();
				$archive->commit();
			}

			// update mtime so the cronjob will keep the file for longer
			$lock = fopen($archive->get_file(), "r+");
			flock($lock, LOCK_SH);
			touch($archive->get_file());
			flock($lock, LOCK_UN);

			assert(filesize($archive->get_file()) > 0);

			$this->load->driver("ddownload");
			$this->ddownload->serveFile($archive->get_file(), "$id.tar.gz", "application/x-gzip");
		}
	}

	/**
	 * Generate a PLS v2 playlist
	 */
	private function _generate_playlist($id)
	{
		$files = $this->mmultipaste->get_files($id);
		$counter = 1;

		$playlist = "[playlist]\n";
		foreach ($files as $file) {
			// only add audio/video files
			$base = explode("/", $file['mimetype'])[0];
			if (!($base === "audio" || $base === "video")) {
				continue;
			}

			$url = site_url($file["id"]);
			$playlist .= sprintf("File%d=%s\n", $counter++, $url);
		}
		$playlist .= sprintf("NumberOfEntries=%d\n", $counter - 1);
		$playlist .= "Version=2\n";

		$this->output->set_content_type('audio/x-scpls');
		$this->output->set_output($playlist);
	}

	function _non_existent()
	{
		$this->data["title"] .= " - Not Found";
		$this->output->set_status_header(404);
		$this->load->view('header', $this->data);
		$this->load->view('file/non_existent', $this->data);
		$this->load->view('footer', $this->data);
	}

	private function _prepare_claim($ids, $lexer)
	{
		if (!$this->muser->logged_in()) {
			$this->muser->require_session();
			// keep the upload but require the user to login
			$last_upload = $this->session->userdata("last_upload");
			if ($last_upload === NULL) {
				$last_upload = array(
					"ids" => [],
					"lexer" => "",
				);
			}
			$last_upload = array(
				"ids" => array_merge($last_upload['ids'], $ids),
				"lexer" => "",
			);
			$this->session->set_userdata("last_upload", $last_upload);
			$this->data["redirect_uri"] = "file/claim_id";
			$this->muser->require_access("basic");
		}

	}

	function _show_url($ids, $lexer)
	{
		$redirect = false;
		$this->_prepare_claim($ids, $lexer);

		foreach ($ids as $id) {
			if ($lexer) {
				$this->data['urls'][] = site_url($id).'/'.$lexer;
			} else {
				$this->data['urls'][] = site_url($id).'/';

				if (count($ids) == 1) {
					$filedata = $this->mfile->get_filedata($id);
					$file = $this->mfile->file($filedata['data_id']);
					$pygments = new \libraries\Pygments($file, $filedata["mimetype"], $filedata["filename"]);
					$lexer = $pygments->should_highlight();

					// If we detected a highlightable file redirect,
					// otherwise show the URL because browsers would just show a DL dialog
					if ($lexer) {
						$redirect = true;
					}
				}
			}
		}

		if ($redirect && count($ids) == 1) {
			redirect($this->data['urls'][0], "location", 303);
		} else {
			$this->load->view('header', $this->data);
			$this->load->view('file/show_url', $this->data);
			$this->load->view('footer', $this->data);
		}
	}

	function upload_form()
	{
		$this->data['title'] .= ' - Upload';
		$this->data['small_upload_size'] = $this->config->item('small_upload_size');
		$this->data['max_upload_size'] = $this->config->item('upload_max_size');
		$this->data['upload_max_age'] = $this->config->item('upload_max_age');

		$this->data['username'] = $this->muser->get_username();

		$repaste_id = $this->input->get("repaste");

		if ($repaste_id) {
			$filedata = $this->mfile->get_filedata($repaste_id);

			$pygments = new \libraries\Pygments($this->mfile->file($filedata["data_id"]), $filedata["mimetype"], $filedata["filename"]);
			if ($filedata !== false && $pygments->can_highlight()) {
				$this->data["textarea_content"] = file_get_contents($this->mfile->file($filedata["data_id"]));
			}
		}

		if (file_exists(FCPATH.'data/client/latest')) {
			$this->var->latest_client = trim(file_get_contents(FCPATH.'data/client/latest'));
			$this->data['client_link'] = base_url().'data/client/fb-'.$this->var->latest_client.'.tar.gz';
		} else {
			$this->data['client_link'] = false;
		}

		$this->load->view('header', $this->data);
		$this->load->view('file/upload_form', $this->data);
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
		session_write_close();
		$id = $this->uri->segment(3);

		if (!$this->mfile->valid_id($id)) {
			return $this->_non_existent();
		}

		$etag = "$id-thumb";
		handle_etag($etag);

		$thumb_size = 150;
		$cache_timeout = 60*60*24*30; # 1 month

		$filedata = $this->mfile->get_filedata($id);
		if (!$filedata) {
			throw new \exceptions\ApiException("file/thumbnail/filedata-unavailable", "Failed to get file data");
		}

		$cache_key = $filedata['data_id'].'_thumb_'.$thumb_size;

		$thumb = cache_function($cache_key, $cache_timeout, function() use ($filedata, $thumb_size){
			$CI =& get_instance();
			$img = new libraries\Image($this->mfile->file($filedata["data_id"]));
			$img->makeThumb($thumb_size, $thumb_size);
			$thumb = $img->get(IMAGETYPE_JPEG);
			return $thumb;
		});

		$this->output->set_header("Cache-Control:max-age=31536000, public");
		$this->output->set_header("Expires: ".date("r", time() + 365 * 24 * 60 * 60));
		$this->output->set_content_type("image/jpeg");
		$this->output->set_output($thumb);
	}

	function upload_history_thumbnails()
	{
		$this->muser->require_access();

		$user = $this->muser->get_userid();

		// TODO: move to \service\files and possibly use \s\f::history()
		$query = $this->db
			->select('files.id, filename, mimetype, files.date, hash, file_storage.id storage_id, filesize, user')
			->from('files')
			->join('file_storage', 'file_storage.id = files.file_storage_id')
			->where('
				(files.user = '.$this->db->escape($user).')
				AND (
					mimetype LIKE \'image%\'
					OR mimetype IN (\'application/pdf\')
				)', null, false)
			->order_by('date', 'desc')
			->get()->result_array();

		foreach($query as $key => $item) {
			assert($item["user"] === $user);
			$item["data_id"] = $item['hash']."-".$item['storage_id'];
			$query[$key]["data_id"] =  $item["data_id"];
			if (!$this->mfile->valid_filedata($item)) {
				unset($query[$key]);
				continue;
			}
			$query[$key]["tooltip"] = \service\files::tooltip($item);
			$query[$key]["orientation"] = libraries\Image::get_exif_orientation($this->mfile->file($item["data_id"]));
		}

		$this->data["items"] = $query;

		$this->load->view('header', $this->data);
		$this->load->view('file/upload_history_thumbnails', $this->data);
		$this->load->view('footer', $this->data);
	}

	public function handle_history_submit()
	{
		$this->muser->require_access("apikey");

		$process = $this->input->post("process");

		$dispatcher = [
			"delete" => function() {
				return $this->do_delete();
			},
			"multipaste" => function() {
				return $this->_append_multipaste_queue();
			},
		];

		if (isset($dispatcher[$process])) {
			$dispatcher[$process]();
		} else {
			throw new \exceptions\UserInputException("file/handle_history_submit/invalid-process-value", "Value in process field not found in dispatch table");
		}
	}

	private function _append_multipaste_queue()
	{
		$ids = $this->input->post_array("ids");
		if ($ids === null) {
			$ids = [];
		}

		$m = new \service\multipaste_queue();
		$m->append($ids);
		redirect("file/multipaste/queue");
	}

	function upload_history()
	{
		$this->muser->require_access("apikey");

		$history = service\files::history($this->muser->get_userid());

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

		foreach ($history["multipaste_items"] as $key => $item) {
			$size = 0;
			$filenames = array();
			$files = array();
			$max_filenames = 10;

			foreach ($item["items"] as $i) {
				$size += $history["items"][$i["id"]]["filesize"];
				$files[] = array(
					"filename" => $history["items"][$i["id"]]['filename'],
					"sort_order" => $i["sort_order"],
				);
			}

			uasort($files, function ($a, $b) {
				return $a['sort_order'] - $b['sort_order'];
			});

			$filenames = array_map(function ($a) {return $a['filename'];}, $files);

			if (count($filenames) > $max_filenames) {
				$filenames = array_slice($filenames, 0, $max_filenames);
				$filenames[] = "...";
			}

			$history["items"][] = array(
				"id" => $item["url_id"],
				"filename" => count($item["items"])." file(s)",
				"mimetype" => "",
				"date" => $item["date"],
				"hash" => "",
				"filesize" => $size,
				"preview_text" => implode("\n", $filenames),
			);
		}

		uasort($history["items"], function($a, $b) {
				return $b["date"] - $a["date"];
		});

		foreach($history["items"] as $key => $item) {
			$history["items"][$key]["filesize"] = format_bytes($item["filesize"]);

			if (isset($item['preview_text'])) {
				$history["items"][$key]["preview_text"] = htmlentities($item['preview_text']);
			}
		}

		$this->data["items"] = $history["items"];
		$this->data["lengths"] = $lengths;
		$this->data["fields"] = $fields;
		$this->data["total_size"] = format_bytes($history["total_size"]);

		$this->load->view('header', $this->data);
		$this->load->view('file/upload_history', $this->data);
		$this->load->view('footer', $this->data);
	}

	function do_delete()
	{
		$this->muser->require_access("apikey");

		$ids = $this->input->post_array("ids");

		$ret = \service\files::delete($ids);

		$this->data["errors"] = $ret["errors"];
		$this->data["deleted_count"] = $ret["deleted_count"];
		$this->data["total_count"] = $ret["total_count"];

		$this->load->view('header', $this->data);
		$this->load->view('file/deleted', $this->data);
		$this->load->view('footer', $this->data);
	}

	function do_multipaste()
	{
		$this->muser->require_access("basic");

		$ids = $this->input->post_array("ids");
		$userid = $this->muser->get_userid();
		$limits = $this->muser->get_upload_id_limits();

		$ret = \service\files::create_multipaste($ids, $userid, $limits);

		return $this->_show_url(array($ret["url_id"]), false);
	}

	/**
	 * Handle submissions from the web form (files and textareas).
	 */
	public function do_websubmit()
	{
		$files = getNormalizedFILES();
		$contents = $this->input->post_array("content");
		$filenames = $this->input->post_array("filename");

		if (!is_array($filenames) || !is_array($contents)) {
			throw new \exceptions\UserInputException('file/websubmit/invalid-form', 'The submitted POST form is invalid');
		}

		$ids = array();
		$ids = array_merge($ids, $this->_handle_textarea($contents, $filenames));
		$ids = array_merge($ids, $this->_handle_files($files));


		if (empty($ids)) {
			throw new \exceptions\UserInputException("file/websubmit/no-input", "You didn't enter any text or upload any files");
		}

		if (count($ids) > 1) {
			$userid = $this->muser->get_userid();
			$limits = $this->muser->get_upload_id_limits();
			$multipaste_id = \service\files::create_multipaste($ids, $userid, $limits)["url_id"];

			$ids[] = $multipaste_id;
			$this->_prepare_claim($ids, false);

			redirect(site_url($multipaste_id)."/");
		}

		$this->_show_url($ids, false);
	}

	private function _handle_files($files)
	{
		$ids = array();

		if (!empty($files)) {
			$limits = $this->muser->get_upload_id_limits();
			$userid = $this->muser->get_userid();
			service\files::verify_uploaded_files($files);

			foreach ($files as $key => $file) {
				$id = $this->mfile->new_id($limits[0], $limits[1]);
				service\files::add_uploaded_file($userid, $id, $file["tmp_name"], $file["name"]);
				$ids[] = $id;
			}
		}

		return $ids;
	}

	private function _handle_textarea($contents, $filenames)
	{
		$ids = array();

		foreach ($contents as $key => $content) {
			$filesize = strlen($content);

			if ($filesize == 0) {
				unset($contents[$key]);
			}

			if ($filesize > $this->config->item('upload_max_size')) {
				throw new \exceptions\RequestTooBigException("file/websubmit/request-too-big", "Error while uploading: Paste too big");
			}
		}

		$limits = $this->muser->get_upload_id_limits();
		$userid = $this->muser->get_userid();

		foreach ($contents as $key => $content) {
			$filename = "stdin";
			if (isset($filenames[$key]) && $filenames[$key] != "") {
				$filename = $filenames[$key];
			}

			$id = $this->mfile->new_id($limits[0], $limits[1]);
			service\files::add_file_data($userid, $id, $content, $filename);
			$ids[] = $id;
		}

		return $ids;
	}

	function claim_id()
	{
		$this->muser->require_access();

		$last_upload = $this->session->userdata("last_upload");

		if ($last_upload === NULL) {
			throw new \exceptions\PublicApiException("file/claim_id/last_upload-failed", "Failed to get last upload data, unable to claim uploads");
		}

		$ids = $last_upload["ids"];
		$errors = array();

		assert(is_array($ids));

		foreach ($ids as $key => $id) {
			$affected = 0;
			$affected += $this->mfile->adopt($id);
			$affected += $this->mmultipaste->adopt($id);

			if ($affected == 0) {
				$errors[] = $id;
			}
		}

		if (!empty($errors)) {
			throw new \exceptions\PublicApiException("file/claim_id/failed", "Failed to claim ".implode(", ", $errors)."");
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
		$this->_require_cli_request();

		\service\files::clean_multipaste_tarballs();

		$oldest_time = (time() - $this->config->item('upload_max_age'));
		$oldest_session_time = (time() - $this->config->item("sess_expiration"));
		$config = array(
			"upload_max_age" => $this->config->item("upload_max_age"),
			"small_upload_size" => $this->config->item("small_upload_size"),
			"sess_expiration" => $this->config->item("sess_expiration"),
		);

		$query = $this->db->select('file_storage_id storage_id, files.id, user, files.date, hash')
			->from('files')
			->join('file_storage', "file_storage.id = files.file_storage_id")
			->where("user", 0)
			->where("files.date <", $oldest_session_time)
			->get()->result_array();

		foreach($query as $row) {
			$row['data_id'] = $row['hash'].'-'.$row['storage_id'];
			\service\files::valid_id($row, $config, $this->mfile, time());
		}

		// 0 age disables age checks
		if ($this->config->item('upload_max_age') == 0) return;

		$query = $this->db->select('hash, files.id, user, files.date, file_storage.id storage_id')
			->from('files')
			->join('file_storage', "file_storage.id = files.file_storage_id")
			->where('files.date <', $oldest_time)
			->get()->result_array();

		foreach($query as $row) {
			$row['data_id'] = $row['hash'].'-'.$row['storage_id'];
			\service\files::valid_id($row, $config, $this->mfile, time());
		}
	}

	/* remove files without database entries */
	function clean_stale_files()
	{
		$this->_require_cli_request();

		\service\files::remove_files_missing_in_db();
		\service\files::remove_files_missing_on_disk();
		\service\files::clean_multipaste_tarballs();
	}

	function nuke_id()
	{
		$this->_require_cli_request();

		$id = $this->uri->segment(3);

		$file_data = $this->mfile->get_filedata($id);

		if (empty($file_data)) {
			echo "unknown id \"$id\"\n";
			return;
		}

		$data_id = $file_data["data_id"];
		$this->mfile->delete_data_id($data_id);
		echo "removed data_id \"$data_id\"\n";
	}

	function update_file_metadata()
	{
		$this->_require_cli_request();

		$chunk = 500;

		$total = $this->db->count_all("file_storage");

		for ($limit = 0; $limit < $total; $limit += $chunk) {
			$query = $this->db->select('hash, id')
				->from('file_storage')
				->limit($chunk, $limit)
				->get()->result_array();

			foreach ($query as $key => $item) {
				$data_id = $item["hash"].'-'.$item['id'];
				$filepath = $this->mfile->file($data_id);
				$mimetype = mimetype($filepath);
				$filesize = filesize($filepath);

				$this->db->where('id', $item['id'])
					->set(array(
						'mimetype' => $mimetype,
						'filesize' => $filesize,
					))
					->update('file_storage');
			}
		}
	}
}

# vim: set noet:
