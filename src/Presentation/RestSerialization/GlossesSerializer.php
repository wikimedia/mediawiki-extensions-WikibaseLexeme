<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use ArrayObject;
use Wikibase\Lexeme\Domain\Model\ReadModel\Glosses;

/**
 * @license GPL-2.0-or-later
 */
class GlossesSerializer {

	public function serialize( Glosses $glosses ): ArrayObject {
		$result = new ArrayObject();
		foreach ( $glosses as $gloss ) {
			$result[$gloss->languageCode] = $gloss->text;
		}
		return $result;
	}

}
