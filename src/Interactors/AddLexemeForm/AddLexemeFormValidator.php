<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeForm;

use LogicException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeFormValidator {

	private ?BlankForm $form = null;

	public function __construct(
		private StatementsValidator $statementsValidator,
		private StatementsValidationErrorConverter $statementsValidationErrorConverter,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( AddLexemeFormRequest $request ): void {
		$serialization = $request->form;

		$form = new BlankForm();
		$form->setRepresentations( $this->deserializeRepresentations( $serialization['representations'] ) );
		$form->setGrammaticalFeatures( array_map(
			static fn ( string $itemId ) => new ItemId( $itemId ),
			$serialization['grammatical_features'] ?? [],
		) );
		foreach ( $this->validateAndDeserializeStatements( $serialization['statements'] ?? [] ) as $statement ) {
			$form->getStatements()->addStatement( $statement );
		}

		$this->form = $form;
	}

	public function getValidatedForm(): BlankForm {
		if ( $this->form === null ) {
			throw new LogicException( 'Must not call getValidatedForm() before validateAndDeserialize()' );
		}

		return $this->form;
	}

	private function deserializeRepresentations( array $representations ): TermList {
		$terms = [];
		foreach ( $representations as $languageCode => $text ) {
			$terms[] = new Term( (string)$languageCode, $text );
		}

		return new TermList( $terms );
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

}
