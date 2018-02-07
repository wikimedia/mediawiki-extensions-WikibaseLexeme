<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0+
 */
class JsonFieldHasWrongType implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @var string[]
	 */
	private $fieldPath;

	/**
	 * @var string
	 */
	private $expectedType;

	/**
	 * @var string
	 */
	private $givenType;

	/**
	 * JsonFieldHasWrongType constructor.
	 * @param string $parameterName
	 * @param string[] $fieldPath
	 * @param string $expectedType
	 * @param string $givenType
	 */
	public function __construct( $parameterName, array $fieldPath, $expectedType, $givenType ) {
		$this->parameterName = $parameterName;
		$this->fieldPath = $fieldPath;
		$this->expectedType = $expectedType;
		$this->givenType = $givenType;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibaselexeme-api-error-json-field-has-wrong-type',
			[
				$this->parameterName,
				implode( '/', $this->fieldPath ),
				$this->expectedType,
				$this->givenType
			]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
