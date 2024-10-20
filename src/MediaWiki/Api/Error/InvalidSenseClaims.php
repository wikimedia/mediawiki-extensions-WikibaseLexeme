<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class InvalidSenseClaims implements ApiError {

	public function asApiMessage( $parameterName, array $path ) {
		return new ApiMessage( new Message(
			'apierror-wikibaselexeme-invalid-sense-claims',
			[ $parameterName, implode( '/', $path ) ]
		), 'bad-request' );
	}

}
