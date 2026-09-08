<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Domain\Model;

use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
class AddStatementEditSummary implements EditSummary {

	public function __construct(
		private readonly ?string $userComment,
		public readonly Statement $statement,
	) {
	}

	public function getUserComment(): ?string {
		return $this->userComment;
	}

}
