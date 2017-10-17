<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0+
 */
class FormMustHaveAtLeastOneRepresentation implements ApiError {

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
	 * @return \ApiMessage
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibase-lexeme-api-error-form-must-have-at-least-one-representation',
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
