<?php

namespace Wikibase\Lexeme\Api\Error;

class JsonFieldIsNotAnItemId implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @var string[]
	 */
	private $path;

	/**
	 * @var string
	 */
	private $given;

	/**
	 * @param string $parameterName
	 * @param string[] $path
	 * @param string $given
	 */
	public function __construct( $parameterName, array $path, $given ) {
		$this->parameterName = $parameterName;
		$this->path = $path;
		$this->given = $given;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibase-lexeme-api-error-json-field-not-item-id',
			[ $this->parameterName, implode( '/', $this->path ), $this->given ]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
