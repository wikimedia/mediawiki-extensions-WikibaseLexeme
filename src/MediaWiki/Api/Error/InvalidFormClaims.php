<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class InvalidFormClaims implements ApiError {

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		return new ApiMessage( new Message(
			'apierror-wikibaselexeme-invalid-form-claims',
			[ $parameterName, implode( '/', $path ) ]
		), 'bad-request' );
	}

}
