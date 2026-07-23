<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use Wikibase\Lexeme\Domain\Model\SenseId;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class Sense {

	public function __construct(
		public readonly SenseId $id,
		public readonly Glosses $glosses,
		public readonly StatementList $statements,
	) {
	}

}
