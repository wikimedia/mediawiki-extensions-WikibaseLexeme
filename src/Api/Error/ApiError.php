<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0+
 */
interface ApiError {

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage();

}
