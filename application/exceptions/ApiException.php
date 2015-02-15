<?php
/*
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace exceptions;

class ApiException extends \Exception {
	private $error_id;
	private $data;

	public function __construct($error_id, $message, $data = null)
	{
		parent::__construct($message);

		$this->error_id = $error_id;
		$this->data = $data;
	}

	public function get_error_id()
	{
		return $this->error_id;
	}

	public function get_data()
	{
		return $this->data;
	}

	public function get_http_error_code()
	{
		return 500;
	}
}
