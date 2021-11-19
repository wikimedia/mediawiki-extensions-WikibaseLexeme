<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermTextCanNotBeEmpty implements ApiError {

	/** @inheritDoc */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'apierror-wikibaselexeme-lexeme-term-text-cannot-be-empty',
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
