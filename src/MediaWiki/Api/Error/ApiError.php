<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
interface ApiError {

	/**
	 * @param string $parameterName
	 * @param array $path
	 *
	 * @return \ApiMessage
	 */
	public function asApiMessage( $parameterName, array $path );

}
