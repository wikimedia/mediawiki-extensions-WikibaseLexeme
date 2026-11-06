<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\PatchLexeme;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Domain\Model\EditMetadata;
use Wikibase\Lexeme\Domain\Model\PatchLexemeEditSummary;
use Wikibase\Lexeme\Domain\Services\LexemeJsonPatcher;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeUpdater;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lexeme\Interactors\GetLatestLexemeRevisionMetadata;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;
use Wikibase\Lexeme\Interactors\UseCaseError;
use Wikibase\Lexeme\Presentation\RestSerialization\LexemeSerializer;

/**
 * @license GPL-2.0-or-later
 */
class PatchLexeme {

	public function __construct(
		private GetLatestLexemeRevisionMetadata $getLatestRevisionMetadata,
		private LexemeRetriever $lexemeRetriever,
		private LexemeSerializer $lexemeSerializer,
		private LexemeJsonPatcher $jsonPatcher,
		private LexemeWriteModelRetriever $lexemeWriteModelRetriever,
		private LexemeUpdater $lexemeUpdater,
		private PatchLexemeValidator $validator,
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
		$patchedSerialization = $this->jsonPatcher->patch(
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->lexemeSerializer->serialize( $this->lexemeRetriever->getLexeme( $lexemeId ) ),
			$request->patch
		);

		$lexeme = $this->lexemeWriteModelRetriever->getLexemeWriteModel( $lexemeId );
		$lexeme->setLemmas( new TermList( array_map(
			static fn ( string $language, string $text ) => new Term( $language, $text ),
			array_keys( $patchedSerialization['lemmas'] ),
			$patchedSerialization['lemmas']
		) ) );

		$lexemeRevision = $this->lexemeUpdater->update(
			$lexeme, // @phan-suppress-current-line PhanTypeMismatchArgumentNullable
			new EditMetadata(
				$request->editTags,
				$request->isBot,
				new PatchLexemeEditSummary( $request->comment ),
			),
		);

		return new PatchLexemeResponse(
			$lexemeRevision->lexeme,
			$lexemeRevision->revisionId,
			$lexemeRevision->lastModified,
		);
	}

}
