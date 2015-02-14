<?php
/*
 * Copyright 2014 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

namespace service;

class user {

	/**
	 * Create a new api key.
	 *
	 * @param userid TODO
	 * @param comment TODO
	 * @param access_level TODO
	 * @return the new key
	 */
	static public function create_apikey($userid, $comment, $access_level)
	{
		$CI =& get_instance();

		$valid_levels = $CI->muser->get_access_levels();
		if (array_search($access_level, $valid_levels) === false) {
			throw new \exceptions\UserInputException("user/validation/access_level/invalid", "Invalid access levels requested.");
		}

		if (strlen($comment) > 255) {
			throw new \exceptions\UserInputException("user/validation/comment/too-long", "Comment may only be 255 chars long.");
		}

		$key = random_alphanum(32);

		$CI->db->set(array(
				'key'          => $key,
				'user'         => $userid,
				'comment'      => $comment,
				'access_level' => $access_level
			))
			->insert('apikeys');

		return $key;
	}

	/**
	 * Get apikeys for a user
	 * @param userid TODO
	 * @return array with the key data
	 */
	static public function apikeys($userid)
	{
		$CI =& get_instance();
		$ret = array();

		$query = $CI->db->select('key, created, comment, access_level')
			->from('apikeys')
			->where('user', $userid)
			->order_by('created', 'desc')
			->get()->result_array();

		// Convert timestamp to unix timestamp
		// TODO: migrate database to integer timestamp and get rid of this
		foreach ($query as &$record) {
			if (!empty($record['created'])) {
				$record['created'] = strtotime($record['created']);
			}
			$ret[$record["key"]] = $record;
		}
		unset($record);

		return array(
			"apikeys" => $ret,
		);
	}
}
