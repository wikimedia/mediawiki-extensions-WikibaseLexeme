<?php

namespace Wikibase\Lexeme\Api\Error;

use ApiMessage;

/**
 * @license GPL-2.0-or-later
 */
class ParameterIsNotFormId implements ApiError {

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
		// Parameter "$1" expected to be a valid Form ID (ex. "L10-F1"), given "$2"
		$message = new \Message(
			'wikibaselexeme-api-error-parameter-not-form-id',
			[ $this->parameterName, $this->given ]
		);
		return new ApiMessage( $message, 'bad-request' );
	}

}
