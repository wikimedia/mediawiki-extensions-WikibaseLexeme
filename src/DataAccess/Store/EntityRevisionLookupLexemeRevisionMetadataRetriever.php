<?php declare( strict_types=1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\LatestLexemeRevisionMetadataResult as MetadataResult;
use Wikibase\Lexeme\Domain\Services\LexemeRevisionMetadataRetriever;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupLexemeRevisionMetadataRetriever implements LexemeRevisionMetadataRetriever {

	public function __construct( private EntityRevisionLookup $revisionLookup ) {
	}

	public function getLatestRevisionMetadata( LexemeId $lexemeId ): MetadataResult {
		return $this->revisionLookup->getLatestRevisionId( $lexemeId )
			->onConcreteRevision( MetadataResult::concreteRevision( ... ) )
			->onNonexistentEntity( static fn () => new MetadataResult() ) // TODO handle not-found
			->onRedirect( static fn () => new MetadataResult() ) // TODO handle redirect
			->map();
	}
}
