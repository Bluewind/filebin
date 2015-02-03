<?php
/*
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace exceptions;

class RequestTooBigException extends UserInputException {
	public function get_http_error_code()
	{
		return 413;
	}
}
