<?php

declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\GetLexeme;

/**
 * @license GPL-2.0-or-later
 */
class GetLexemeRequest {

	public function __construct(
		public readonly string $lexemeId
	) {
	}

}
