<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeSense;

use Wikibase\Lexeme\Domain\Model\AddSenseEditSummary;
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
class AddLexemeSense {

	use UpdateExceptionHandler;

	public function __construct(
		private LexemeWriteModelRetriever $lexemeRetriever,
		private LexemeUpdater $lexemeUpdater,
		private AddLexemeSenseValidator $validator,
		private LexemeRevisionMetadataRetriever $metadataRetriever,
		private AssertUserIsAuthorized $assertUserIsAuthorized,
	) {
	}

	/**
	 * @throws LexemeRedirect
	 * @throws UseCaseError
	 */
	public function execute( AddLexemeSenseRequest $request ): AddLexemeSenseResponse {
		$this->validator->validate( $request );
		$lexemeId = $this->validator->getValidatedLexemeId();
		$sense = $this->validator->getValidatedSense();

		$metadata = $this->metadataRetriever->getLatestRevisionMetadata( $lexemeId );

		if ( !$metadata->lexemeExists() ) {
			throw UseCaseError::newResourceNotFound( 'lexeme' );
		}
		if ( $metadata->isRedirect() ) {
			throw new LexemeRedirect( $metadata->getRedirectTarget() );
		}

		$this->assertUserIsAuthorized->checkEditPermissions(
			$request->username === null ? User::newAnonymous() : User::withUsername( $request->username ),
			$lexemeId,
		);

		$lexeme = $this->lexemeRetriever->getLexemeWriteModel( $lexemeId );
		$lexeme->addOrUpdateSense( $sense );

		$lexemeRevision = $this->executeWithExceptionHandling(
			fn () => $this->lexemeUpdater->update(
				$lexeme, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
				new EditMetadata(
					$request->editTags,
					$request->isBot,
					new AddSenseEditSummary( $request->comment, $sense ),
				),
			)
		);

		return new AddLexemeSenseResponse(
			$lexemeRevision->lexeme->senses->getById( $sense->getId() ),
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}

}
