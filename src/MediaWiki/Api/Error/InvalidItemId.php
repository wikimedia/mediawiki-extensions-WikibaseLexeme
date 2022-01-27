<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

use Message;

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

	/** @inheritDoc */
	public function asApiMessage( $parameterName, array $path ) {
		return new \ApiMessage( new Message(
			'apierror-wikibaselexeme-invalid-item-id',
			[ $parameterName, implode( '/', $path ), $this->given ]
		), 'bad-request' );
	}

}
