<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use LogicException;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lexeme\Domain\DummyObjects\BlankForm;
use Wikibase\Lexeme\Domain\DummyObjects\BlankSense;
use Wikibase\Lexeme\Domain\Model\CreateLexemeEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\WriteModel\LexemeToCreate;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\FormValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\ItemIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeTermsValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\SenseValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementsValidator;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeValidator {

	private ?LexemeToCreate $lexeme = null;
	private ?EditMetadata $editMetadata = null;

	public function __construct(
		private LexemeTermsValidator $lexemeTermsValidator,
		private ItemIdValidator $itemIdValidator,
		private StatementsValidator $statementsValidator,
		private StatementsValidationErrorConverter $statementsValidationErrorConverter,
		private FormValidator $formValidator,
		private SenseValidator $senseValidator,
		private EditMetadataRequestValidator $editMetadataRequestValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( CreateLexemeRequest $request ): void {
		$serialization = $request->lexeme;

		if ( !array_key_exists( 'lemmas', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'lemmas' );
		}
		$lemmas = $this->lexemeTermsValidator->validateAndDeserialize( $serialization['lemmas'], '/lexeme/lemmas' );
		if ( !array_key_exists( 'lexical_category', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'lexical_category' );
		}
		$lexicalCategory = $this->itemIdValidator->validateItemId(
			$serialization['lexical_category'],
			'/lexeme/lexical_category'
		);
		if ( !array_key_exists( 'language', $serialization ) ) {
			throw UseCaseError::newMissingField( '/lexeme', 'language' );
		}
		$language = $this->itemIdValidator->validateItemId( $serialization['language'], '/lexeme/language' );
		$statements = $this->validateAndDeserializeStatements( $serialization['statements'] ?? [] );
		$forms = $this->validateAndDeserializeForms( $serialization['forms'] ?? [] );
		$senses = $this->validateAndDeserializeSenses( $serialization['senses'] ?? [] );

		$this->editMetadataRequestValidator->validate( $request->editTags, $request->comment );

		$this->lexeme = new LexemeToCreate(
			$language,
			$lexicalCategory,
			$lemmas,
			$statements,
			forms: $forms,
			senses: $senses,
		);
		$this->editMetadata = new EditMetadata(
			$request->editTags,
			$request->isBot,
			new CreateLexemeEditSummary( $request->comment ),
		);
	}

	public function getValidatedLexeme(): LexemeToCreate {
		if ( $this->lexeme === null ) {
			throw new LogicException( 'Must not call getValidatedLexeme() before validateAndDeserialize()' );
		}

		return $this->lexeme;
	}

	public function getValidatedEditMetadata(): EditMetadata {
		if ( $this->editMetadata === null ) {
			throw new LogicException( 'Must not call getValidatedEditMetadata() before validateAndDeserialize()' );
		}

		return $this->editMetadata;
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeStatements( mixed $statements ): StatementList {
		if ( !is_array( $statements ) ) {
			throw UseCaseError::newInvalidValue( '/lexeme/statements' );
		}

		$validationError = $this->statementsValidator->validateNewStatements( $statements, '/lexeme/statements' );
		if ( $validationError !== null ) {
			throw $this->statementsValidationErrorConverter->toUseCaseError( $validationError );
		}

		return $this->statementsValidator->getValidatedStatements();
	}

	/**
	 * @return BlankForm[]
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeForms( mixed $forms ): array {
		if ( !is_array( $forms ) || !array_is_list( $forms ) ) {
			throw UseCaseError::newInvalidValue( '/lexeme/forms' );
		}

		$deserializedForms = [];
		foreach ( $forms as $index => $form ) {
			$path = "/lexeme/forms/$index";
			if ( !is_array( $form ) || ( $form && array_is_list( $form ) ) ) {
				throw UseCaseError::newInvalidValue( $path );
			}
			$deserializedForms[] = $this->formValidator->validateAndDeserialize( $form, $path );
		}

		return $deserializedForms;
	}

	/**
	 * @return BlankSense[]
	 * @throws UseCaseError
	 */
	private function validateAndDeserializeSenses( mixed $senses ): array {
		if ( !is_array( $senses ) || !array_is_list( $senses ) ) {
			throw UseCaseError::newInvalidValue( '/lexeme/senses' );
		}

		$deserializedSenses = [];
		foreach ( $senses as $index => $sense ) {
			$path = "/lexeme/senses/$index";
			if ( !is_array( $sense ) || ( $sense && array_is_list( $sense ) ) ) {
				throw UseCaseError::newInvalidValue( $path );
			}
			$deserializedSenses[] = $this->senseValidator->validateAndDeserialize( $sense, $path );
		}

		return $deserializedSenses;
	}
}
