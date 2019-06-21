<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class JsonFieldIsRequired implements ApiError {

	/**
	 * @var string
	 */
	private $field;

	/**
	 * @param string $field
	 */
	public function __construct( $field ) {
		$this->field = $field;
	}

	/**
	 * @inheritDoc
	 */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'apierror-wikibaselexeme-json-field-required',
			[ $parameterName, implode( '/', $path ), $this->field ]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
