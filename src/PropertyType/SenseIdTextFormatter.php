<?php

namespace Wikibase\Lexeme\PropertyType;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lexeme\DataModel\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class SenseIdTextFormatter implements EntityIdFormatter {

	/**
	 * @param SenseId $value
	 *
	 * @return string plain text
	 */
	public function formatEntityId( EntityId $value ) {
		return $value->getSerialization();
	}

}
