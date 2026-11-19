<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\PatchLexeme;

use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Validation\LexemeTermsValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedLexemeValidator {

	public function __construct( private LexemeTermsValidator $lexemeTermsValidator ) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize(
		array $patchedSerialization,
		Lexeme $originalLexeme
	): Lexeme {

		$this->assertNoMissingMandatoryFields( $patchedSerialization );
		$this->assertNoIllegalModification( $patchedSerialization, $originalLexeme );
		$this->assertValidLemmas( $patchedSerialization[ 'lemmas' ] );

		$patchedLexeme = $originalLexeme->copy();
		$patchedLexeme->setLemmas( $this->lexemeTermsValidator->getValidatedTerms() );
		return $patchedLexeme;
			// TODO check that all fields possible in lexeme are valid
			// such as forms, senses, statements, that have their own validators, etc
	}

	/**
	 * @throws UseCaseError
	 */
	private function assertNoMissingMandatoryFields( array $patchedSerialization ): void {
		foreach ( [ 'lemmas', 'lexical_category', 'language' ] as $field ) {
			if ( !isset( $patchedSerialization[$field] ) ) {
				throw UseCaseError::newMissingFieldInPatchResult( '', $field );
			}
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function assertNoIllegalModification(
		array $serialization,
		Lexeme $originalLexeme
	): void {
		if (
			isset( $serialization['id'] ) &&
			$serialization['id'] !== $originalLexeme->getId()->getSerialization()
		) {
			throw UseCaseError::newPatchResultModifiedReadOnlyValue( '/id' );
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function assertValidLemmas( mixed $patchedLemmas ): void {
		$validationError = $this->lexemeTermsValidator->validate( $patchedLemmas, '/lemmas' );
		if ( $validationError ) {
			throw match ( $validationError->code ) {
				LexemeTermsValidator::CODE_INVALID_KEY => UseCaseError::newPatchResultInvalidKey(
					$validationError->context[ LexemeTermsValidator::CONTEXT_PATH ],
					$validationError->context[ LexemeTermsValidator::CONTEXT_KEY ]
				),
				LexemeTermsValidator::CODE_VALUE_TOO_LONG => UseCaseError::newPatchResultValueTooLong(
					$validationError->context[ LexemeTermsValidator::CONTEXT_PATH ],
					$validationError->context[ LexemeTermsValidator::CONTEXT_LIMIT ]
				),
				LexemeTermsValidator::CODE_INVALID_VALUE => UseCaseError::newPatchResultInvalidValue(
					$validationError->context[ LexemeTermsValidator::CONTEXT_VALUE ],
					$validationError->context[ LexemeTermsValidator::CONTEXT_PATH ]
				),
			};
		}
	}
}
