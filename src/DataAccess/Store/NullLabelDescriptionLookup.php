<?php

namespace Wikibase\Lexeme\DataAccess\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;

/**
 * @license GPL-2.0-or-later
 */
class NullLabelDescriptionLookup implements LabelDescriptionLookup {

	public function getLabel( EntityId $entityId ) {
		return null;
	}

	public function getDescription( EntityId $entityId ) {
		return null;
	}

}
