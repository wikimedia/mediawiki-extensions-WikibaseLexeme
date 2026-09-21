<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeStatement;

use LogicException;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\UseCaseRequestValidation\EditMetadataRequestValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\LexemeIdValidator;
use Wikibase\Lexeme\UseCaseRequestValidation\StatementsValidationErrorConverter;
use Wikibase\Repo\Domains\Statements\Application\Validation\StatementValidator;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementValidator {

	private ?LexemeId $lexemeId = null;
	private ?Statement $statement = null;

	public function __construct(
		private LexemeIdValidator $lexemeIdValidator,
		private StatementValidator $statementValidator,
		private StatementsValidationErrorConverter $statementsValidationErrorConverter,
		private EditMetadataRequestValidator $editMetadataRequestValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( AddLexemeStatementRequest $request ): void {
		$this->lexemeId = $this->lexemeIdValidator->validate( $request->lexemeId );

		$statementValidationError = $this->statementValidator->validate( $request->statement, '/statement' );
		if ( $statementValidationError !== null ) {
			throw $this->statementsValidationErrorConverter->toUseCaseError( $statementValidationError );
		}
		$this->statement = $this->statementValidator->getValidatedStatement();

		$this->editMetadataRequestValidator->validate( $request->editTags, $request->comment );
	}

	public function getValidatedLexemeId(): LexemeId {
		if ( $this->lexemeId === null ) {
			throw new LogicException( 'Must not call getValidatedLexemeId() before validate()' );
		}

		return $this->lexemeId;
	}

	public function getValidatedStatement(): Statement {
		if ( $this->statement === null ) {
			throw new LogicException( 'Must not call getValidatedStatement() before validate()' );
		}

		return $this->statement;
	}

}
