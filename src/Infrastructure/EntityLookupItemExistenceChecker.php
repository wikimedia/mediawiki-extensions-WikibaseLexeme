<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Infrastructure;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lexeme\Validation\ItemExistenceChecker;

/**
 * @license GPL-2.0-or-later
 */
class EntityLookupItemExistenceChecker implements ItemExistenceChecker {

	public function __construct( private EntityLookup $entityLookup ) {
	}

	public function exists( ItemId $itemId ): bool {
		return $this->entityLookup->hasEntity( $itemId );
	}

}
