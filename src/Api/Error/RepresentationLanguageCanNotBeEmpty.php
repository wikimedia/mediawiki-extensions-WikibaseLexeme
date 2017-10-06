<?php

namespace Wikibase\Lexeme\Api\Error;

class RepresentationLanguageCanNotBeEmpty implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @var string[]
	 */
	private $fieldPath;

	/**
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
			'wikibase-lexeme-api-error-representation-language-cannot-be-empty',
			[]
		);
		return new \ApiMessage(
			$message,
			'unprocessable-request',
			[
				'parameterName' => $this->parameterName,
				'fieldPath' => $this->fieldPath
			]
		);
	}

}
