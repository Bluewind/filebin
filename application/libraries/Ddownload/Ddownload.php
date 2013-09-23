<?php
/*
 * Copyright 2013 Pierre Schmitz <pierre@archlinux.de>
 *
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */

abstract class Ddownload_Driver extends CI_Driver {

	abstract public function serveFile($file, $filename, $type);
}

class Ddownload extends CI_Driver_Library {

	protected $_adapter = null;

	protected $valid_drivers = array(
		'ddownload_php', 'ddownload_nginx', 'ddownload_lighttpd'
	);

	function __construct()
	{
		$CI =& get_instance();

		$this->_adapter = $CI->config->item('download_driver');
	}

	public function serveFile($file, $filename, $type)
	{
		$this->{$this->_adapter}->serveFile($file, $filename, $type);
	}
}
