<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermLanguageCanNotBeEmpty implements ApiError {

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'apierror-wikibaselexeme-lexeme-term-language-cannot-be-empty',
			[]
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
