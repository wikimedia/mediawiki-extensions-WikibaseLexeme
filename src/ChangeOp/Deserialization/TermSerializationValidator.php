<?php

namespace Wikibase\Lexeme\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;

/**
 * @license GPL-2.0-or-later
 */
class TermSerializationValidator {

	/**
	 * According to BCP 47 (https://tools.ietf.org/html/bcp47)
	 */
	const PRIVATE_USE_SUBTAG_SEPARATOR = '-x-';

	/**
	 * @var TermChangeOpSerializationValidator
	 */
	private $serializationValidator;

	public function __construct( $serializationValidator ) {
		$this->serializationValidator = $serializationValidator;
	}

	public function validate( $serialization, $languageCode ) {
		list( $languageCode ) = explode( self::PRIVATE_USE_SUBTAG_SEPARATOR, $languageCode );
		if ( is_array( $serialization ) && array_key_exists( 'language', $serialization ) ) {
			list( $language ) = explode( self::PRIVATE_USE_SUBTAG_SEPARATOR, $serialization['language'] );
			$serialization['language'] = $language;
		}

		$this->serializationValidator->validateTermSerialization( $serialization, $languageCode );
	}

}
