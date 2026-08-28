<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeStatement;

use Wikibase\Repo\Domains\Statements\Domain\ReadModel\Statement;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementResponse {

	public function __construct(
		public readonly Statement $statement,
		public readonly int $revisionId,
		public readonly string $lastModified,
	) {
	}

}
