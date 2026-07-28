<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model\ReadModel;

use Wikibase\Lexeme\Domain\Model\FormId;
use Wikibase\Repo\Domains\Statements\Domain\ReadModel\StatementList;

/**
 * @license GPL-2.0-or-later
 */
class Form {

	public function __construct(
		public readonly FormId $id,
		public readonly Representations $representations,
		public readonly GrammaticalFeatures $grammaticalFeatures,
		public readonly StatementList $statements,
	) {
	}
}
