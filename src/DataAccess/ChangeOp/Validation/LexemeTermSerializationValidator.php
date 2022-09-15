<?php

declare( strict_types = 1 );

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
	 * Validate the structure of the given $serialization.
	 *
	 * If the term is not being removed,
	 * callers should also call {@link LexemeTermSerializationValidator::validateLanguage()}
	 * afterwards.
	 *
	 * @param array $serialization (checking that it is an array is part of the validation)
	 * @param ValidationContext $context
	 */
	public function validateStructure( $serialization, ValidationContext $context ) {
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
	}

	/**
	 * Check that the language inside the $serialization is valid
	 * and consistent with the given $language.
	 *
	 * The $serialization must already have been
	 * {@link LexemeTermSerializationValidator::validateStructure() validated for structural correctness}.
	 *
	 * @param string $language (checking that it is a string is part of the validation)
	 */
	public function validateLanguage( $language, array $serialization, ValidationContext $context ) {
		$this->languageValidator->validate( $language, $context, $serialization['value'] ?? null );

		if ( $language !== $serialization['language'] ) {
			$context->addViolation( new LanguageInconsistent( $language, $serialization['language'] ) );
		}
	}

}
