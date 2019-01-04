<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class JsonFieldIsNotAnItemId implements ApiError {

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

	/**
	 * @see ApiError::asApiMessage()
	 *
	 * @param string $parameterName
	 * @param string[] $path
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
		'apierror-wikibaselexeme-json-field-not-item-id',
			[ $parameterName, implode( '/', $path ), json_encode( $this->given ) ]
		);
		// TODO: should be something more specific than bad-request
		return new \ApiMessage( $message, 'bad-request' );
	}

}
