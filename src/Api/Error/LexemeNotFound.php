<?php

namespace Wikibase\Lexeme\Api\Error;

use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0+
 */
class LexemeNotFound implements ApiError {

	/**
	 * @var LexemeId
	 */
	private $lexemeId;

	public function __construct( LexemeId $lexemeId ) {
		$this->lexemeId = $lexemeId;
	}

	/**
	 * @return \ApiMessage
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibase-lexeme-api-error-lexeme-not-found',
			[ $this->lexemeId->serialize() ]
		);
		return new \ApiMessage( $message, 'not-found' );
	}

}
