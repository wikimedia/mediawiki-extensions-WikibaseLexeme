<?php declare( strict_types = 1 );

namespace Wikibase\Lexeme\Interactors\CreateLexeme;

/**
 * @license GPL-2.0-or-later
 */
class CreateLexemeRequest {

	public function __construct(
		public readonly array $lexeme,
		public readonly array $editTags,
		public readonly bool $isBot,
		public readonly ?string $comment,
		public readonly ?string $username,
	) {
	}
}
