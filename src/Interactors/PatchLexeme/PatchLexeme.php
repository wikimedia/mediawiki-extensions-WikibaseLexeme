<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\PatchLexeme;

use LogicException;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\PatchLexemeEditSummary;
use Wikibase\Lexeme\Domain\Model\User;
use Wikibase\Lexeme\Domain\Services\Exceptions\PatchPathException;
use Wikibase\Lexeme\Domain\Services\Exceptions\PatchTestConditionFailedException;
use Wikibase\Lexeme\Domain\Services\LexemeJsonPatcher;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\AssertUserIsAuthorized;
use Wikibase\Lexeme\Interactors\GetLatestLexemeRevisionMetadata;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UpdateExceptionHandler;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;

/**
 * @license GPL-2.0-or-later
 */
class PatchLexeme {

	use UpdateExceptionHandler;

	public function __construct(
		private GetLatestLexemeRevisionMetadata $getLatestRevisionMetadata,
		private LexemeRetriever $lexemeRetriever,
		private LexemeSerializer $lexemeSerializer,
		private LexemeJsonPatcher $jsonPatcher,
		private LexemeWriteModelRetriever $lexemeWriteModelRetriever,
		private LexemeUpdater $lexemeUpdater,
		private PatchLexemeValidator $validator,
		private AssertUserIsAuthorized $assertUserIsAuthorized,
		private PatchedLexemeValidator $patchedLexemeValidator,
	) {
	}

	/**
	 * @throws UseCaseError
	 * @throws LexemeRedirect
	 */
	public function execute( PatchLexemeRequest $request ): PatchLexemeResponse {
		$this->validator->validate( $request );
		$lexemeId = $this->validator->getValidatedLexemeId();

		$this->getLatestRevisionMetadata->assertLexemeExists( $lexemeId );
		$this->assertUserIsAuthorized->checkEditPermissions(
			$request->username === null ? User::newAnonymous() : User::withUsername( $request->username ),
			$lexemeId
		);

		$patchedSerialization = $this->patchLexemeSerialization(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->lexemeSerializer->serialize( $this->lexemeRetriever->getLexeme( $lexemeId ) ),
			$request->patch
		);

		$originalLexeme = $this->lexemeWriteModelRetriever->getLexemeWriteModel( $lexemeId );

		$patchedLexeme = $this->patchedLexemeValidator->validateAndDeserialize(
			$patchedSerialization,
			$originalLexeme // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
		);

		$lexemeRevision = $this->executeWithExceptionHandling(
			fn () => $this->lexemeUpdater->update(
				$patchedLexeme,
				new EditMetadata(
					$request->editTags,
					$request->isBot,
					new PatchLexemeEditSummary( $request->comment ),
				),
			),
		);

		return new PatchLexemeResponse(
			$lexemeRevision->lexeme,
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}

	/**
	 * @throws UseCaseError
	 */
	private function patchLexemeSerialization( array $serialization, array $patch ): mixed {
		try {
			return $this->jsonPatcher->patch( $serialization, $patch );
		} catch ( PatchPathException $e ) {
			throw UseCaseError::newPatchTargetNotFound( "/patch/{$e->getOpIndex()}/{$e->getField()}" );
		} catch ( PatchTestConditionFailedException $e ) {
			$opIndex = array_search( $e->getOperation(), $patch );
			if ( !is_int( $opIndex ) ) {
				throw new LogicException( "The failed test operation wasn't found in the original patch document" );
			}
			throw UseCaseError::newPatchTestFailed( "/patch/$opIndex", $e->getActualValue() );
		}
	}

}
