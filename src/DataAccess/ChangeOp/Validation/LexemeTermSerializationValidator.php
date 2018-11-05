<?php

namespace Wikibase\Lexeme\DataAccess\ChangeOp\Validation;

use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldHasWrongType;
use Wikibase\Lexeme\MediaWiki\Api\Error\JsonFieldIsRequired;
use Wikibase\Lexeme\MediaWiki\Api\Error\LanguageInconsistent;
use Wikibase\Lexeme\MediaWiki\Api\Error\LexemeTermTextCanNotBeEmpty;
use Wikibase\Lexeme\Presentation\ChangeOp\Deserialization\ValidationContext;

/**
 * @license GPL-2.0-or-later
 */
class LexemeTermSerializationValidator {

	/**
	 * @var LexemeTermLanguageValidator
	 */
	private $languageValidator;

	public function __construct( LexemeTermLanguageValidator $languageValidator ) {
		$this->languageValidator = $languageValidator;
	}

	/**
	 * @param string $language
	 * @param array $serialization
	 * @param ValidationContext $context
	 */
	public function validate( $language, $serialization, ValidationContext $context ) {
		if ( !is_array( $serialization ) ) {
			$context->addViolation( new JsonFieldHasWrongType( 'array', gettype( $serialization ) ) );
			return;
		}

		if ( !array_key_exists( 'language', $serialization ) ) {
			$context->addViolation( new JsonFieldIsRequired( 'language' ) );
			return;
		}

		if ( !array_key_exists( 'remove', $serialization ) ) {
			if ( !array_key_exists( 'value', $serialization ) ) {
				$context->addViolation( new JsonFieldIsRequired( 'value' ) );
				return;
			}

			if ( !is_string( $serialization['value'] ) ) {
				$context->addViolation(
					new JsonFieldHasWrongType( 'string', gettype( $serialization['value'] ) )
				);
			}

			if ( $serialization['value'] === '' ) {
				$context->addViolation( new LexemeTermTextCanNotBeEmpty() );
			}
		}

		$this->languageValidator->validate( $language, $context );

		if ( $language !== $serialization['language'] ) {
			$context->addViolation( new LanguageInconsistent( $language, $serialization['language'] ) );
		}
	}

}
