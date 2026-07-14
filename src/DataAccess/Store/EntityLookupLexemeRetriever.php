<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\Domain\Model\Lexeme as LexemeWriteModel;
use Wikibase\Lexeme\Domain\Model\LexemeId;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;
use Wikibase\Lexeme\Domain\Services\LexemeRetriever;

/**
 * @license GPL-2.0-or-later
 */
class EntityLookupLexemeRetriever implements LexemeRetriever {

	public function __construct(
		private EntityLookup $entityLookup
	) {
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
		$entity = $this->entityLookup->getEntity( $lexemeId );
		if ( $entity === null ) {
			return null;
		}
		'@phan-var LexemeWriteModel $entity';
		return $entity;
	}

}
