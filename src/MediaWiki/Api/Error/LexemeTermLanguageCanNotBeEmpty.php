<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermLanguageCanNotBeEmpty implements ApiError {

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		$message = new Message(
			'apierror-wikibaselexeme-lexeme-term-language-cannot-be-empty',
			[]
		);
		return new ApiMessage(
			$message,
			'unprocessable-request',
			[
				'parameterName' => $parameterName,
				'fieldPath' => $path,
			]
		);
	}

}
