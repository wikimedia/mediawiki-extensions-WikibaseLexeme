<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use Wikibase\Lexeme\Domain\Model\SenseId;

/**
 * @license GPL-2.0-or-later
 */
class Sense {

	public function __construct(
		public readonly SenseId $id,
		public readonly Glosses $glosses,
	) {
	}

}
