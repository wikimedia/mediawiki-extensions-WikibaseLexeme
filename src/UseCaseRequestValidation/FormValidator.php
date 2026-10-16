<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\UseCaseRequestValidation;

use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;

/**
 * @license GPL-2.0-or-later
 */
class FormValidator {

	public function __construct(
		private LexemeTermsValidator $lexemeTermsValidator,
		private StatementsValidator $statementsValidator,
		private StatementsValidationErrorConverter $statementsValidationErrorConverter,
		private ItemIdValidator $itemIdValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $serialization, string $basePath ): BlankForm {

		if ( !array_key_exists( 'representations', $serialization ) ) {
			throw UseCaseError::newMissingField( $basePath, 'representations' );
		}

		$form = new BlankForm();

		$form->setRepresentations( $this->lexemeTermsValidator->validateAndDeserialize(
			$serialization['representations'],
			"$basePath/representations",
		) );
		$form->setGrammaticalFeatures( $this->validateAndDeserializeGrammaticalFeatures(
			$serialization['grammatical_features'] ?? [],
			$basePath,
		) );
		foreach ( $this->validateAndDeserializeStatements(
			$serialization['statements'] ?? [],
			$basePath
		) as $statement ) {
			$form->getStatements()->addStatement( $statement );
		}

		return $form;
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeStatements( mixed $statements, string $basePath ): StatementList {
		if ( !is_array( $statements ) ) {
			throw UseCaseError::newInvalidValue( "$basePath/statements" );
		}

		$validationError = $this->statementsValidator->validateNewStatements( $statements, "$basePath/statements" );
		if ( $validationError !== null ) {
			throw $this->statementsValidationErrorConverter->toUseCaseError( $validationError );
		}

		return $this->statementsValidator->getValidatedStatements();
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeGrammaticalFeatures( mixed $grammaticalFeatures, string $basePath ): array {
		if ( !is_array( $grammaticalFeatures ) || !array_is_list( $grammaticalFeatures ) ) {
			throw UseCaseError::newInvalidValue( "$basePath/grammatical_features" );
		}

		return array_map(
			fn ( mixed $itemId, int $index ) => $this->itemIdValidator->validateItemId(
				$itemId,
				"$basePath/grammatical_features/$index"
			),
			$grammaticalFeatures,
			array_keys( $grammaticalFeatures )
		);
	}
}
