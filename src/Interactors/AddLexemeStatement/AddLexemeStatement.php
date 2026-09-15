<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeStatement;

use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\Lexeme\Domain\Model\AddStatementEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UpdateExceptionHandler;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeStatement {

	use UpdateExceptionHandler;

	public function __construct(
		private LexemeWriteModelRetriever $lexemeRetriever,
		private LexemeUpdater $lexemeUpdater,
		private GuidGenerator $guidGenerator,
		private AddLexemeStatementValidator $validator,
		private LexemeRevisionMetadataRetriever $metadataRetriever,
		private AssertUserIsAuthorized $assertUserIsAuthorized,
	) {
	}

	/**
	 * @throws LexemeRedirect
	 * @throws UseCaseError
	 */
	public function execute( AddLexemeStatementRequest $request ): AddLexemeStatementResponse {
		$this->validator->validate( $request );
		$lexemeId = $this->validator->getValidatedLexemeId();
		$statement = $this->validator->getValidatedStatement();
		$metaData = $this->metadataRetriever->getLatestRevisionMetadata( $lexemeId );

		if ( !$metaData->lexemeExists() ) {
			throw UseCaseError::newResourceNotFound( 'lexeme' );
		}
		if ( $metaData->isRedirect() ) {
			throw new LexemeRedirect( $metaData->getRedirectTarget() );
		}

		$this->assertUserIsAuthorized->checkEditPermissions(
			$request->username === null ? User::newAnonymous() : User::withUsername( $request->username ),
			$lexemeId
		);

		$statementId = $this->guidGenerator->newStatementId( $lexemeId );
		$statement->setGuid( (string)$statementId );

		$lexeme = $this->lexemeRetriever->getLexemeWriteModel( $lexemeId );
		$lexeme->getStatements()->addStatement( $statement );

		$lexemeRevision = $this->executeWithExceptionHandling(
			fn () => $this->lexemeUpdater->update(
				$lexeme, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$request->editTags,
				$request->isBot,
				new AddStatementEditSummary( $request->comment, $statement ),
			),
			)
		);

		return new AddLexemeStatementResponse(
			$lexemeRevision->lexeme->statements->getStatementById( $statementId ),
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}
}
