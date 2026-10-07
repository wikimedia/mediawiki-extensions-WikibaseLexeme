<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeSense;

use Wikibase\Lexeme\Domain\Model\ReadModel\Sense;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeSenseResponse {

	public function __construct(
		public readonly Sense $sense,
		public readonly int $revisionId,
		public readonly string $lastModified,
	) {
	}

}
