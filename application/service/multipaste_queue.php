<?php
/*
 * Copyright 2016 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace service;

class multipaste_queue {

	public function __construct($session = null, $mfile = null, $mmultipaste = null) {
		$CI =& get_instance();

		$this->session = $session;
		$this->mfile = $mfile;
		$this->mmultipaste = $mmultipaste;

		if ($this->session === null) {
			$this->session = $CI->session;
		}

		if ($this->mfile === null) {
			$CI->load->model("mfile");
			$this->mfile = $CI->mfile;
		}

		if ($this->mmultipaste === null) {
			$CI->load->model("mmultipaste");
			$this->mmultipaste = $CI->mmultipaste;
		}
	}

	/**
	 * Append ids to the queue
	 *
	 * @param array ids
	 * @return void
	 */
	public function append(array $ids) {
		$old_ids = $this->get();

		# replace multipaste ids with their corresponding paste ids
		$ids = array_map(function($id) {return array_values($this->resolve_multipaste($id));}, $ids);
		$ids = array_reduce($ids, function($a, $b) {return array_merge($a, $b);}, []);

		$ids = array_unique(array_merge($old_ids, $ids));
		$this->set($ids);
	}

	/**
	 * Return array of ids in a multipaste if the argument id is a multipaste.
	 * Otherwise return an array containing just the argument id.
	 *
	 * @param id
	 * @return array of ids
	 */
	private function  resolve_multipaste($id) {
		if (strpos($id, "m-") === 0) {
			if ($this->mmultipaste->valid_id($id)) {
				return array_map(function($filedata) {return $filedata['id'];}, $this->mmultipaste->get_files($id));
			}
		}
		return [$id];
	}

	/**
	 * Get the queue
	 *
	 * @return array of ids
	 */
	public function get() {
		$ids = $this->session->userdata("multipaste_queue");
		if ($ids === false) {
			$ids = [];
		}

		assert(is_array($ids));
		return $ids;
	}

	/**
	 * Set the queue to $ids
	 *
	 * @param array ids
	 * @return void
	 */
	public function set(array $ids) {
		$ids = array_filter($ids, function($id) {return $this->mfile->valid_id($id);});

		$this->session->set_userdata("multipaste_queue", $ids);
	}

}
