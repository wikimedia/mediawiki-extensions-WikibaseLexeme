<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\GetLexeme;

use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Interactors\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class GetLexeme {

	public function __construct(
		private LexemeRetriever $lexemeRetriever,
		private LexemeRevisionMetadataRetriever $metadataRetriever,
		private GetLexemeValidator $validator,
	) {
	}

	/**
	 * @throws LexemeRedirect
	 */
	public function execute( GetLexemeRequest $request ): GetLexemeResponse {
		$this->validator->validate( $request );
		$lexemeId = $this->validator->getValidatedLexemeId();

		$metaData = $this->metadataRetriever->getLatestRevisionMetadata( $lexemeId );

		if ( !$metaData->lexemeExists() ) {
			throw UseCaseError::newLexemeNotFound();
		}

		if ( $metaData->isRedirect() ) {
			throw new LexemeRedirect( $metaData->getRedirectTarget() );
		}

		$lexeme = $this->lexemeRetriever->getLexeme( $lexemeId );

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable Lexeme exists
		return new GetLexemeResponse( $lexeme, $metaData->getRevisionId(), $metaData->getRevisionTimestamp() );
	}

}
