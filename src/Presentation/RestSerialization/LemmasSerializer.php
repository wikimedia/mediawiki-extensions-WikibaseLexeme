<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Presentation\RestSerialization;

use ArrayObject;
use Wikibase\Lexeme\Domain\Model\ReadModel\Lemmas;

/**
 * @license GPL-2.0-or-later
 */
class LemmasSerializer {

	public function serialize( Lemmas $lemmas ): ArrayObject {
		$result = new ArrayObject();
		foreach ( $lemmas as $lemma ) {
			$result[$lemma->languageCode] = $lemma->text;
		}
		return $result;
	}

}
