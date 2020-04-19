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
	 * Refer to Muser->get_access_levels() for a list of valid access levels.
	 *
	 * @param userid ID of the user
	 * @param comment free text comment describing the api key/it's usage/allowing to identify the key
	 * @param access_level access level of the key
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
	 * @param userid ID of the user
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

	/**
	 * Create an invitation key for a user
	 * @param userid id of the user
	 * @return key the created invitation key
	 */
	static public function create_invitation_key($userid) {
		$CI =& get_instance();

		$invitations = $CI->db->select('user')
			->from('actions')
			->where('user', $userid)
			->where('action', 'invitation')
			->count_all_results();

		if ($invitations + 1 > $CI->config->item('max_invitation_keys')) {
			throw new \exceptions\InsufficientPermissionsException("user/invitation-limit", "You can't create more invitation keys at this time.");
		}

		$key = random_alphanum(12, 16);

		$CI->db->set(array(
				'key'    => $key,
				'user'   => $userid,
				'date'   => time(),
				'action' => 'invitation'
			))
			->insert('actions');

		return $key;
	}

	/**
	 * Remove an invitation key belonging to a user
	 * @param userid id of the user
	 * @param key key to remove
	 * @return number of removed keys
	 */
	static public function delete_invitation_key($userid, $key) {
		$CI =& get_instance();

		$CI->db
			->where('key', $key)
			->where('user', $userid)
			->delete('actions');

		return $CI->db->affected_rows();
	}
}
