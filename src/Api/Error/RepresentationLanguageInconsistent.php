<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class RepresentationLanguageInconsistent implements ApiError {

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
	private $expectedLanguage;

	/**
	 * @var string
	 */
	private $givenLanguage;

	/**
	 * @param string $parameterName
	 * @param string[] $fieldPath
	 * @param string $expectedLanguage
	 * @param string $givenLanguage
	 */
	public function __construct(
		$parameterName,
		array $fieldPath,
		$expectedLanguage,
		$givenLanguage
	) {
		$this->parameterName = $parameterName;
		$this->fieldPath = $fieldPath;
		$this->expectedLanguage = $expectedLanguage;
		$this->givenLanguage = $givenLanguage;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibaselexeme-api-error-representation-language-inconsistent',
			[ $this->expectedLanguage, $this->givenLanguage ]
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
