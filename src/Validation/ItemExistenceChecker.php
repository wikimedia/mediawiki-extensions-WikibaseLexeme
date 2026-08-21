<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Validation;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
interface ItemExistenceChecker {

	public function exists( ItemId $itemId ): bool;

}
