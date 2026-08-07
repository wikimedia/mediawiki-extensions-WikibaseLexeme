<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeResponse {

	public function __construct(
		public readonly Lexeme $lexeme
	) {
	}

}
