<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use ArrayObject;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
class GrammaticalFeatures extends ArrayObject {

	public function __construct( ItemId ...$itemIds ) {
		parent::__construct( $itemIds );
	}

}
