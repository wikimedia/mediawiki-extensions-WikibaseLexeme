<?php

namespace Wikibase\Lexeme\Api\Error;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class InvalidFormClaims implements ApiError {

	public function asApiMessage( $parameterName, array $path ) {
		return new \ApiMessage( new Message(
			'wikibaselexeme-api-error-invalid-form-claims',
			[ $parameterName, implode( '/', $path ) ]
		), 'bad-request' );
	}

}
