<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lexeme\Domain\Services\LexemeWriteModelRetriever;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupLexemeRetriever implements LexemeRetriever, LexemeWriteModelRetriever {

	public function __construct(
		private EntityRevisionLookup $entityRevisionLookup,
		private LexemeReadModelConverter $lexemeReadModelConverter,
	) {
	}

	public function getLexeme( LexemeId $lexemeId ): ?Lexeme {
		$lexeme = $this->getLexemeWriteModel( $lexemeId );

		if ( $lexeme === null ) {
			return null;
		}

		return $this->lexemeReadModelConverter->convert( $lexeme );
	}

	public function getLexemeWriteModel( LexemeId $lexemeId ): ?LexemeWriteModel {
		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $lexemeId );
		} catch ( RevisionedUnresolvedRedirectException ) {
			return null;
		}

		if ( !$entityRevision ) {
			return null;
		}

		/** @var LexemeWriteModel $lexeme */
		$lexeme = $entityRevision->getEntity();
		'@phan-var LexemeWriteModel $lexeme';

		return $lexeme;
	}

}
