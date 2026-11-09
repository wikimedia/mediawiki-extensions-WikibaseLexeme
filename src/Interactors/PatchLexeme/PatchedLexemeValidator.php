<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\PatchLexeme;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedLexemeValidator {

	public function validateAndDeserialize(
		array $patchedSerialization,
		Lexeme $originalLexeme
	): Lexeme {

		$this->assertNoMissingMandatoryFields( $patchedSerialization );

		$patchedLexeme = $originalLexeme->copy();

		$patchedLexeme->setLemmas( new TermList( array_map(
			static fn ( string $language, string $text ) => new Term( $language, $text ),
			array_keys( $patchedSerialization[ 'lemmas' ] ),
			$patchedSerialization[ 'lemmas' ]
		) ) );
		return $patchedLexeme;
			// TODO check that all fields possible in lexeme are valid
			// such as forms, senses, statements, that have their own validators, etc
	}

	private function assertNoMissingMandatoryFields( array $patchedSerialization ): void {
		foreach ( [ 'lemmas', 'lexical_category', 'language' ] as $field ) {
			if ( !isset( $patchedSerialization[$field] ) ) {
				throw UseCaseError::newMissingFieldInPatchResult( '', $field );
			}
		}
	}
}
