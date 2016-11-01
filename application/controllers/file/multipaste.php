<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class Multipaste extends MY_Controller {

	function __construct() {
		parent::__construct();

		$this->load->model('mfile');
		$this->load->model('mmultipaste');
	}

	public function append_multipaste_queue() {
		$this->muser->require_access("basic");

		$ids = $this->input->post("ids");
		if ($ids === false) {
			$ids = [];
		}

		$m = new \service\multipaste_queue();
		$m->append($ids);

		redirect("file/multipaste/queue");
	}

	public function review_multipaste() {
		$this->muser->require_access("basic");

		$this->load->view('header', $this->data);
		$this->load->view('file/review_multipaste', $this->data);
		$this->load->view('footer', $this->data);
	}

	public function queue() {
		$this->muser->require_access("basic");

		$m = new \service\multipaste_queue();
		$ids = $m->get();

		$this->data['ids'] = $ids;
		$this->data['items'] = array_map(function($id) {return $this->_get_multipaste_item($id);}, $ids);

		$this->load->view('header', $this->data);
		$this->load->view('file/multipaste/queue', $this->data);
		$this->load->view('footer', $this->data);
	}

	public function form_submit() {
		$this->muser->require_access("basic");

		$ids = $this->input->post('ids');
		$process = $this->input->post('process');

		if ($ids === false) {
			$ids = [];
		}

		$m = new \service\multipaste_queue();
		$m->set($ids);

		$dispatcher = [
			'save' => function() use ($ids, $m) {
				redirect("file/multipaste/queue");
			},
			'create' => function() use ($ids, $m) {
				$userid = $this->muser->get_userid();
				$limits = $this->muser->get_upload_id_limits();
				$ret = \service\files::create_multipaste($ids, $userid, $limits);
				$m->set([]);
				redirect($ret['url_id'].'/');
			},
		];

		if (isset($dispatcher[$process])) {
			$dispatcher[$process]();
		} else {
			throw new \exceptions\UserInputException("file/multipaste/form_submit/invalid-process-value", "Value in process field not found in dispatch table");
		}
	}

	public function ajax_submit() {
		$this->muser->require_access("basic");
		$ids = $this->input->post('ids');

		if ($ids === false) {
			$ids = [];
		}

		$m = new \service\multipaste_queue();
		$m->set($ids);
	}

	private function _get_multipaste_item($id) {
		$filedata = $this->mfile->get_filedata($id);
		$item = [];
		$item['id'] = $filedata['id'];
		$item['tooltip'] = \service\files::tooltip($filedata);
		$item['title'] = $filedata['filename'];
		if (\libraries\Image::type_supported($filedata["mimetype"])) {
			$item['thumbnail'] = site_url("file/thumbnail/".$filedata['id']);
		}

		return $item;
	}

}
