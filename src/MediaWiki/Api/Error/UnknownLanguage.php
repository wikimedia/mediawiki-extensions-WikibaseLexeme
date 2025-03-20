<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class UnknownLanguage implements ApiError {
	/**
	 * @var string
	 */
	private $given;

	/** @var string|null */
	private $termText;

	/**
	 * @param string $given
	 * @param string|null $termText for context, if available
	 */
	public function __construct( string $given, $termText = null ) {
		$this->given = $given;
		$this->termText = $termText;
	}

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		if ( $this->termText !== null ) {
			$message = new Message(
				'apierror-wikibaselexeme-unknown-language-withtext',
				[ $parameterName, implode( '/', $path ), $this->given, $this->termText ]
			);
		} else {
			$message = new Message(
				'apierror-wikibaselexeme-unknown-language',
				[ $parameterName, implode( '/', $path ), $this->given ]
			);
		}
		return new ApiMessage( $message, 'not-recognized-language' );
	}

}
