<?php
/*
 * Copyright 2017 Florian "Bluewind" Pritz <bluewind@server-speed.net>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

class MY_Input extends CI_Input {
	public function post($key = null, $xss_clean = false) {
		$ret = parent::post($key, $xss_clean);
		if (is_array($ret) || is_object($ret)) {
			$data = [
				"key" => $key,
				"ret" => $ret
			];
			if (preg_match("/^[a-zA-Z0-9_\.-]+$/", $key)) {
				throw new \exceptions\UserInputException("input/invalid-form-field", "Invalid input in field $key", $data);
			} else {
				throw new \exceptions\UserInputException("input/invalid-form-field", "Invalid input", $data);
			}
		}
		return $ret;
	}

	public function post_array($key) {
		$ret = parent::post($key);
		if ($ret === null) {
			return null;
		} elseif (!is_array($ret)) {
			$data = [
				"key" => $key,
				"ret" => $ret
			];
			throw new \exceptions\UserInputException("input/invalid-form-field", "Invalid input", $data);
		}
		return $ret;
	}
}
