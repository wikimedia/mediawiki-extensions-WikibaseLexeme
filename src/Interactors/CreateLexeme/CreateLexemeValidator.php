<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validate( CreateLexemeRequest $request ): void {
		$serialization = $request->lexeme;

		if ( !array_key_exists( 'lemmas', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'lemmas' );
		}
		if ( !$serialization['lemmas'] ) {
			throw UseCaseError::newInvalidValue( '/lexeme/lemmas' );
		}
		if ( !array_key_exists( 'lexical_category', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'lexical_category' );
		}
		if ( !array_key_exists( 'language', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'language' );
		}
	}

}
