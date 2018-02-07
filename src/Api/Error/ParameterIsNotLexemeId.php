<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0+
 */
class ParameterIsNotLexemeId implements ApiError {

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

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		// Parameter "$1" expected to be a valid Lexeme ID (ex. "L10"), given "$2"
		$message = new \Message(
			'wikibaselexeme-api-error-parameter-not-lexeme-id',
			[ $this->parameterName, $this->given ]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
