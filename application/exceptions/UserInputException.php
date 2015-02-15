<?php
/*
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace exceptions;

class UserInputException extends PublicApiException {
	public function get_http_error_code()
	{
		return 400;
	}
}
