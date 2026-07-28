<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use Wikibase\Lexeme\Domain\Model\ReadModel\GrammaticalFeatures;

/**
 * @license GPL-2.0-or-later
 */
class GrammaticalFeaturesSerializer {

	public function serialize( GrammaticalFeatures $grammaticalFeatures ): array {
		$itemIds = [];
		foreach ( $grammaticalFeatures as $itemId ) {
			$itemIds[] = $itemId->getSerialization();
		}
		return $itemIds;
	}

}
