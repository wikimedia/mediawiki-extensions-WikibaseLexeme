<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class LanguageInconsistent implements ApiError {

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

	/** @inheritDoc */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'apierror-wikibaselexeme-language-inconsistent',
			[ $parameterName, implode( '/', $path ), $this->expectedLanguage, $this->givenLanguage ]
		);
		return new \ApiMessage(
			$message,
			'inconsistent-language',
			[
				'parameterName' => $parameterName,
				'fieldPath' => $path
			]
		);
	}

}
