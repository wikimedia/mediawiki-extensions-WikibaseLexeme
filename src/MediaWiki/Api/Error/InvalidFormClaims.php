<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use Message;

/**
 * @license GPL-2.0-or-later
 */
class InvalidFormClaims implements ApiError {

	public function asApiMessage( $parameterName, array $path ) {
		return new \ApiMessage( new Message(
			'apierror-wikibaselexeme-invalid-form-claims',
			[ $parameterName, implode( '/', $path ) ]
		), 'bad-request' );
	}

}
