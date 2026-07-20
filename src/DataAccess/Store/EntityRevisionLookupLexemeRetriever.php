<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;

/**
 * @license GPL-2.0-or-later
 */
class EntityRevisionLookupLexemeRetriever implements LexemeRetriever {

	public function __construct( private EntityRevisionLookup $entityRevisionLookup ) {
	}

	public function getLexeme( LexemeId $lexemeId ): ?Lexeme {
		$lexeme = $this->getLexemeWriteModel( $lexemeId );

		if ( $lexeme === null ) {
			return null;
		}

		// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
		return new Lexeme( $lexeme->getId() );
	}

	private function getLexemeWriteModel( LexemeId $lexemeId ): ?LexemeWriteModel {
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
