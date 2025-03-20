<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use MediaWiki\Api\ApiMessage;
use MediaWiki\Message\Message;

/**
 * @license GPL-2.0-or-later
 */
class InvalidItemId implements ApiError {
	/**
	 * @var string
	 */
	private $given;

	/**
	 * @param string $given
	 */
	public function __construct( $given ) {
		$this->given = $given;
	}

	public function asApiMessage( string $parameterName, array $path ): ApiMessage {
		return new ApiMessage( new Message(
			'apierror-wikibaselexeme-invalid-item-id',
			[ $parameterName, implode( '/', $path ), $this->given ]
		), 'bad-request' );
	}

}
