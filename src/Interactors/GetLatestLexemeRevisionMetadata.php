<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\Interactors;

use Wikibase\Lexeme\Domain\Model\Lexeme;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lexeme\Interactors\GetLexeme\LexemeRedirect;

/**
 * @license GPL-2.0-or-later
 */
class GetLatestLexemeRevisionMetadata {

	public function __construct( private LexemeRevisionMetadataRetriever $metadataRetriever ) {
	}

	/**
	 * @throws LexemeRedirect if the Lexeme is a redirect
	 * @throws UseCaseError if the Lexeme does not exist
	 *
	 * @return array{int, string}
	 */
	public function execute( LexemeId $id ): array {
		$metaDataResult = $this->metadataRetriever->getLatestRevisionMetadata( $id );

		if ( !$metaDataResult->lexemeExists() ) {
			throw UseCaseError::newResourceNotFound( Lexeme::ENTITY_TYPE );
		}

		if ( $metaDataResult->isRedirect() ) {
			throw new LexemeRedirect( $metaDataResult->getRedirectTarget() );
		}

		return [ $metaDataResult->getRevisionId(), $metaDataResult->getRevisionTimestamp() ];
	}

	/**
	 * @throws LexemeRedirect if the Lexeme is a redirect
	 * @throws UseCaseError if the Lexeme does not exist
	 */
	public function assertLexemeExists( LexemeId $id ): void {
		$this->execute( $id );
	}

}
