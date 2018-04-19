<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class RepresentationLanguageInconsistent implements ApiError {

	/**
	 * @var string
	 */
	private $expectedLanguage;

	/**
	 * @var string
	 */
	private $givenLanguage;

	/**
	 * @param string $expectedLanguage
	 * @param string $givenLanguage
	 */
	public function __construct( $expectedLanguage, $givenLanguage ) {
		$this->expectedLanguage = $expectedLanguage;
		$this->givenLanguage = $givenLanguage;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'wikibaselexeme-api-error-representation-language-inconsistent',
			[ $parameterName, implode( '/', $path ), $this->expectedLanguage, $this->givenLanguage ]
		);
		return new \ApiMessage(
			$message,
			'unprocessable-request',
			[
				'parameterName' => $parameterName,
				'fieldPath' => $path
			]
		);
	}

}
