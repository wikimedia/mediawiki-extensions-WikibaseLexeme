<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeStatement;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\EditSummaryAction;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Repo\Domains\Statements\Application\Serialization\StatementDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeStatement {

	public function __construct(
		private LexemeWriteModelRetriever $lexemeRetriever,
		private LexemeUpdater $lexemeUpdater,
		private StatementDeserializer $statementDeserializer,
		private GuidGenerator $guidGenerator,
	) {
	}

	public function execute( AddLexemeStatementRequest $request ): AddLexemeStatementResponse {
		$lexemeId = new LexemeId( $request->lexemeId );
		$statementId = $this->guidGenerator->newStatementId( $lexemeId );

		$statement = $this->statementDeserializer->deserialize( $request->statement );
		$statement->setGuid( (string)$statementId );

		$lexeme = $this->lexemeRetriever->getLexemeWriteModel( $lexemeId );
		$lexeme->getStatements()->addStatement( $statement );

		$lexemeRevision = $this->lexemeUpdater->update(
			$lexeme, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$request->editTags,
				$request->isBot,
				$request->comment,
				EditSummaryAction::ADD_STATEMENT,
			),
		);

		return new AddLexemeStatementResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable the statement was just added
			$lexemeRevision->lexeme->statements->getStatementById( $statementId ),
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}

}
