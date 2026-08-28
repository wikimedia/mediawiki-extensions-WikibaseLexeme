<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\AddLexemeStatement;

/**
 * @license GPL-2.0-or-later
 */
class AddLexemeStatementRequest {

	public function __construct(
		public readonly string $lexemeId,
		public readonly array $statement,
		public readonly array $editTags,
		public readonly bool $isBot,
		public readonly ?string $comment,
	) {
	}

}
