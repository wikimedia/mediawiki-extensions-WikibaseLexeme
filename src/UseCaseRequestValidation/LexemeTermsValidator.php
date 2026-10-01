<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\LexemeTermLanguageCodeValidator;

/**
 * Validates and deserializes Lexeme terms, i.e. lemmas, Form representations and Sense glosses.
 *
 * @license GPL-2.0-or-later
 */
class LexemeTermsValidator {

	public function __construct(
		private LexemeTermLanguageCodeValidator $languageCodeValidator,
		private int $maxLength,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( mixed $terms, string $path ): TermList {
		if ( !is_array( $terms ) || !$terms || array_is_list( $terms ) ) {
			throw UseCaseError::newInvalidValue( $path );
		}

		$deserializedTerms = [];
		foreach ( $terms as $languageCode => $text ) {
			$languageCode = (string)$languageCode;
			if ( !$this->languageCodeValidator->isValid( $languageCode ) ) {
				throw UseCaseError::newInvalidKey( $path, $languageCode );
			}
			$deserializedTerms[] = new Term( $languageCode, $this->validateText( $text, "$path/$languageCode" ) );
		}

		return new TermList( $deserializedTerms );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateText( mixed $text, string $path ): string {
		if ( !is_string( $text ) ) {
			throw UseCaseError::newInvalidValue( $path );
		}
		$text = trim( $text );
		if ( $text === '' || preg_match( '/[\v\t]/u', $text ) ) {
			throw UseCaseError::newInvalidValue( $path );
		}
		if ( mb_strlen( $text ) > $this->maxLength ) {
			throw UseCaseError::newValueTooLong( $path, $this->maxLength );
		}

		return $text;
	}

}
