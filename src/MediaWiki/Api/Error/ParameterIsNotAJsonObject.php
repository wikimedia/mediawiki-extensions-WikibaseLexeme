<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * TODO Special. Only happens in RequestParser
 *
 * @license GPL-2.0-or-later
 */
class ParameterIsNotAJsonObject implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @var string
	 */
	private $given;

	/**
	 * @param string $parameterName
	 * @param string $given
	 */
	public function __construct( $parameterName, $given ) {
		$this->parameterName = $parameterName;
		$this->given = $given;
	}

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		$message = new Message(
			'apierror-wikibaselexeme-parameter-invalid-json-object',
			[ $this->parameterName, $this->given ]
		);
		return new ApiMessage( $message, 'bad-request' );
	}

}
