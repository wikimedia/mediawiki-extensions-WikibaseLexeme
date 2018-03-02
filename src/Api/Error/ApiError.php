<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
interface ApiError {

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage();

}
