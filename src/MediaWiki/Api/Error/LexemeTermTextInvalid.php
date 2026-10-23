<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;
use ValueValidators\Error;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermTextInvalid implements ApiError {

	private Error $error;

	public function __construct( Error $error ) {
		$this->error = $error;
	}

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		// Messages: wikibase-validator-bad-type, wikibase-validator-too-short,
		// wikibase-validator-too-long, wikibase-validator-malformed-value
		$message = new Message(
			'wikibase-validator-' . $this->error->getCode(),
			$this->error->getParameters(),
		);
		return new ApiMessage(
			$message,
			'unprocessable-request',
			[
				'parameterName' => $parameterName,
				'fieldPath' => $path,
			],
		);
	}

}
