<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;

/**
 * @license GPL-2.0-or-later
 */
class SenseValidator {

	public function __construct(
		private LexemeTermsValidator $lexemeTermsValidator,
		private StatementsValidator $statementsValidator,
		private StatementsValidationErrorConverter $statementsValidationErrorConverter,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $serialization, string $basePath ): BlankSense {
		if ( !array_key_exists( 'glosses', $serialization ) ) {
			throw UseCaseError::newMissingField( $basePath, 'glosses' );
		}

		$sense = new BlankSense();
		$sense->getGlosses()->addAll( $this->lexemeTermsValidator->validateAndDeserialize(
			$serialization['glosses'],
			"$basePath/glosses",
		) );

		$statements = $serialization['statements'] ?? [];
		if ( !is_array( $statements ) ) {
			throw UseCaseError::newInvalidValue( "$basePath/statements" );
		}
		$validationError = $this->statementsValidator->validateNewStatements( $statements, "$basePath/statements" );
		if ( $validationError !== null ) {
			throw $this->statementsValidationErrorConverter->toUseCaseError( $validationError );
		}
		foreach ( $this->statementsValidator->getValidatedStatements() as $statement ) {
			$sense->getStatements()->addStatement( $statement );
		}

		return $sense;
	}

}
