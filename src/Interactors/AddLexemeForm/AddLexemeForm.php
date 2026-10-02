<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeForm;

use Wikibase\Lexeme\Domain\Model\AddFormEditSummary;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeForm {

	public function __construct(
		private LexemeWriteModelRetriever $lexemeRetriever,
		private LexemeUpdater $lexemeUpdater,
		private AddLexemeFormValidator $validator,
		private LexemeRevisionMetadataRetriever $metadataRetriever,
		private AssertUserIsAuthorized $assertUserIsAuthorized,
	) {
	}

	/**
	 * @throws LexemeRedirect
	 * @throws UseCaseError
	 */
	public function execute( AddLexemeFormRequest $request ): AddLexemeFormResponse {
		$this->validator->validate( $request );
		$form = $this->validator->getValidatedForm();
		$lexemeId = new LexemeId( $request->lexemeId );
		$metadata = $this->metadataRetriever->getLatestRevisionMetadata( $lexemeId );

		if ( !$metadata->lexemeExists() ) {
			throw UseCaseError::newResourceNotFound( 'lexeme' );
		}
		if ( $metadata->isRedirect() ) {
			throw new LexemeRedirect( $metadata->getRedirectTarget() );
		}
		$this->assertUserIsAuthorized->checkEditPermissions(
			$request->username === null ? User::newAnonymous() : User::withUsername( $request->username ),
			$lexemeId
		);

		$lexeme = $this->lexemeRetriever->getLexemeWriteModel( $lexemeId );
		$lexeme->addOrUpdateForm( $form );

		$lexemeRevision = $this->lexemeUpdater->update(
			$lexeme, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$request->editTags,
				$request->isBot,
				new AddFormEditSummary( $request->comment, $form ),
			),
		);

		return new AddLexemeFormResponse(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable the Form was just added
			$lexemeRevision->lexeme->forms->getById( $form->getId() ),
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}

}
