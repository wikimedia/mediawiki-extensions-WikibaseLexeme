<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use Wikibase\Lexeme\Domain\Model\LexemeId;

/**
 * @license GPL-2.0-or-later
 */
class Lexeme {

	public function __construct(
		public readonly LexemeId $id,
		public readonly Lemmas $lemmas
	) {
	}

}
