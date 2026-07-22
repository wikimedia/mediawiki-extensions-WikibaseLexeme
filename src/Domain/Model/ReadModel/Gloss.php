<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
class Gloss {

	public function __construct(
		public readonly string $languageCode,
		public readonly string $text,
	) {
	}

	public static function fromTerm( Term $term ): self {
		return new self(
			$term->getLanguageCode(),
			$term->getText()
		);
	}

}
