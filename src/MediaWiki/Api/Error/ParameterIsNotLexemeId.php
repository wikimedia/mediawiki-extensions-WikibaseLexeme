<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * TODO Special. Only happens in RequestParser
 *
 * ParameterIsNotLexemeId
 * @license GPL-2.0-or-later
 */
class ParameterIsNotLexemeId implements ApiError {

	/**
	 * @var string
	 */
	private $given;

	/**
	 * @param string $given
	 */
	public function __construct( $given ) {
		$this->given = $given;
	}

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		// Parameter "$1" expected to be a valid Lexeme ID (ex. "L10"), given "$2"
		$message = new Message(
			'apierror-wikibaselexeme-parameter-not-lexeme-id',
			[ $parameterName, json_encode( $this->given ) ]
		);
		return new ApiMessage( $message, 'bad-request' );
	}

}
