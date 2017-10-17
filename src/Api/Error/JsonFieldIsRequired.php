<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0+
 */
class JsonFieldIsRequired implements ApiError {

	/**
	 * @var
	 */
	private $parameterName;

	/**
	 * @var string[]
	 */
	private $fieldPath;

	/**
	 * JsonFieldIsRequired constructor.
	 * @param string $parameterName
	 * @param string[] $fieldPath
	 */
	public function __construct( $parameterName, array $fieldPath ) {
		$this->parameterName = $parameterName;
		$this->fieldPath = $fieldPath;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibase-lexeme-api-error-json-field-required',
			[ $this->parameterName, implode( '/', $this->fieldPath ) ]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
