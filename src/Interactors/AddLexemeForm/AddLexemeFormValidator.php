<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeForm;

use LogicException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\ItemIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeFormValidator {

	private ?BlankForm $form = null;

	public function __construct(
		private LexemeTermsValidator $lexemeTermsValidator,
		private StatementsValidator $statementsValidator,
		private StatementsValidationErrorConverter $statementsValidationErrorConverter,
		private ItemIdValidator $itemIdValidator,
		private EditMetadataRequestValidator $editMetadataRequestValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddLexemeFormRequest $request ): void {
		$serialization = $request->form;

		if ( !array_key_exists( 'representations', $serialization ) ) {
			throw UseCaseError::newMissingField( '/form', 'representations' );
		}

		$form = new BlankForm();
		$form->setRepresentations( $this->lexemeTermsValidator->validateAndDeserialize(
			$serialization['representations'],
			'/form/representations',
		) );
		$form->setGrammaticalFeatures( $this->validateAndDeserializeGrammaticalFeatures(
			$serialization['grammatical_features'] ?? []
		) );
		foreach ( $this->validateAndDeserializeStatements( $serialization['statements'] ?? [] ) as $statement ) {
			$form->getStatements()->addStatement( $statement );
		}

		$this->form = $form;

		$this->editMetadataRequestValidator->validate( $request->editTags, $request->comment );
	}

	public function getValidatedForm(): BlankForm {
		if ( $this->form === null ) {
			throw new LogicException( 'Must not call getValidatedForm() before validateAndDeserialize()' );
		}

		return $this->form;
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeStatements( mixed $statements ): StatementList {
		if ( !is_array( $statements ) ) {
			throw UseCaseError::newInvalidValue( '/form/statements' );
		}

		$validationError = $this->statementsValidator->validateNewStatements( $statements, '/form/statements' );
		if ( $validationError !== null ) {
			throw $this->statementsValidationErrorConverter->toUseCaseError( $validationError );
		}

		return $this->statementsValidator->getValidatedStatements();
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeGrammaticalFeatures( mixed $grammaticalFeatures ): array {
		if ( !is_array( $grammaticalFeatures ) || !array_is_list( $grammaticalFeatures ) ) {
			throw UseCaseError::newInvalidValue( '/form/grammatical_features' );
		}

		return array_map(
			fn ( mixed $itemId, int $index ) => $this->itemIdValidator->validateItemId(
				$itemId,
				"/form/grammatical_features/$index"
			),
			$grammaticalFeatures,
			array_keys( $grammaticalFeatures )
		);
	}
}
