<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\PatchLexeme;

use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;

/**
 * @license GPL-2.0-or-later
 */
class PatchLexemeResponse {

	public function __construct(
		public readonly Lexeme $lexeme,
		public readonly int $revisionId,
		public readonly string $lastModified,
	) {
	}

}
