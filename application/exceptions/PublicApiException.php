<?php
/*
 * Licensed under AGPLv3
 * (see COPYING for full license text)
 *
 */
namespace exceptions;

class PublicApiException extends ApiException {
	public function __toString()
	{
		return $this->getMessage();
	}
}
