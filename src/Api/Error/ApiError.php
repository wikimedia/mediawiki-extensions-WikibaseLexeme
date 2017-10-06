<?php

namespace Wikibase\Lexeme\Api\Error;

interface ApiError {

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage();

}
