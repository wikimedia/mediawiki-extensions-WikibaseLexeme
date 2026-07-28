<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use ArrayObject;
use Wikibase\Lexeme\Domain\Model\ReadModel\Representations;

/**
 * @license GPL-2.0-or-later
 */
class RepresentationsSerializer {

	public function serialize( Representations $representations ): ArrayObject {
		$result = new ArrayObject();
		foreach ( $representations as $representation ) {
			$result[$representation->languageCode] = $representation->text;
		}
		return $result;
	}

}
