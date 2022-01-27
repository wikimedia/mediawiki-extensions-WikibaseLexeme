<?php

namespace Wikibase\Lexeme\MediaWiki\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class JsonFieldHasWrongType implements ApiError {

	/**
	 * @var string
	 */
	private $expectedType;

	/**
	 * @var string
	 */
	private $givenType;

	/**
	 * @param string $expectedType
	 * @param string $givenType
	 */
	public function __construct( $expectedType, $givenType ) {
		$this->expectedType = $expectedType;
		$this->givenType = $givenType;
	}

	/** @inheritDoc */
	public function asApiMessage( $parameterName, array $path ) {
		$message = new \Message(
			'apierror-wikibaselexeme-json-field-has-wrong-type',
			[
				$parameterName,
				implode( '/', $path ),
				$this->expectedType,
				$this->givenType
			]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
