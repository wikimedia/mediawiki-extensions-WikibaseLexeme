<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\GetLexeme;

use Wikibase\Lexeme\Domain\Model\ReadModel\Lexeme;

/**
 * @license GPL-2.0-or-later
 */
class GetLexemeResponse {

	public function __construct(
		public readonly Lexeme $lexeme
	) {
	}

}
